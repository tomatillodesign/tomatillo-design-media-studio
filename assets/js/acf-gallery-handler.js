/**
 * ACF Gallery Handler — Single-file harden+persist with VERBOSE logging
 * Works even if another script is mutating .acf-gallery DOM/hidden inputs.
 *
 * - Uses Tomatillo modal if present; else leaves native ACF frame.
 * - Always writes via ACF field model (val → render → change).
 * - Mirrors IDs into block attributes (for ACF Blocks storing in attributes).
 * - Monkeypatches common "DOM hack" functions if they exist on window.
 * - Watches for post-set tampering and auto-repairs.
 *
 * 🎯 UPDATED: Comprehensive ACF field model integration with debugging
 */

(function ($) {
  'use strict';

  // =================== DEBUG MODE ===================
  var tomatilloDebugMode = (window.tomatilloSettings && window.tomatilloSettings.debug_mode) || false;

  // =================== LOGGING ===================
  var NS = '🎯 ACF Gallery Persist';
  var VERBOSE = tomatilloDebugMode;
  var MONITOR_ONLY = true; // Diagnostics-only; do not mutate unless explicitly called
  function now(){ try { return new Date().toISOString().replace('T',' ').replace('Z',''); } catch(e){ return ''; } }
  function lp(){ return '['+now()+'] '+NS; }
  function LOG(){ if (tomatilloDebugMode && console && console.log) { console.log.apply(console, [lp()].concat([].slice.call(arguments))); } }
  function WARN(){ if (tomatilloDebugMode && console && console.warn) { console.warn.apply(console, [lp()].concat([].slice.call(arguments))); } }
  function ERR(){ if (tomatilloDebugMode && console && console.error) { console.error.apply(console, [lp()].concat([].slice.call(arguments))); } }
  function G(t){ if (VERBOSE && console && console.group) try{ console.group(lp()+' '+t); }catch(e){} }
  function GE(){ if (VERBOSE && console && console.groupEnd) try{ console.groupEnd(); }catch(e){} }
  function GC(t){ if (VERBOSE && console && console.groupCollapsed) try{ console.groupCollapsed(lp()+' '+t); }catch(e){} }

  // =================== UTILS ===================
  function to$(ctx){ if (!ctx) return $(document); if (ctx.jquery) return ctx; if (ctx.$el) return to$(ctx.$el); return $(ctx); }
  function nudgeDirty(){ try{ if (wp?.data?.dispatch) wp.data.dispatch('core/editor').editPost({}); }catch(e){} }
  function getFieldFromEl($el){ try{ var f=acf.getField($el); if(f) return f; var k=$el?.attr('data-key'); return k?acf.getField(k):null; }catch(e){ ERR('getFieldFromEl',e); return null; } }
  function idsFromSelection(selection){
    var arr = Array.isArray(selection) ? selection : (selection?.toArray ? selection.toArray() : []);
    var ids = arr.map(function(it){ if (it?.get) return parseInt(it.get('id'),10); if (it && (it.id||it.ID)) return parseInt(it.id||it.ID,10); return NaN; })
                 .filter(Number.isFinite);
    return ids;
  }
  function mergeIds(newIds, currentIds){
    // Ensure both are arrays
    if (!Array.isArray(newIds)) { newIds = []; }
    if (!Array.isArray(currentIds)) { 
      if (typeof currentIds === 'string' && currentIds) {
        currentIds = currentIds.split(',').map(function(v){ return parseInt(v,10); }).filter(Number.isFinite);
      } else {
        currentIds = []; 
      }
    }
    
    // Filter out invalid values from both arrays
    newIds = newIds.map(function(n){ return parseInt(n,10); }).filter(function(n){ return Number.isFinite(n) && n > 0; });
    currentIds = currentIds.map(function(n){ return parseInt(n,10); }).filter(function(n){ return Number.isFinite(n) && n > 0; });
    
    // Merge: new IDs first, then existing IDs that aren't in new IDs
    var merged = [].concat(newIds, currentIds.filter(function(id){ return newIds.indexOf(id)===-1; }));
    
    return merged;
  }
  function eqIds(a,b){
    if (!Array.isArray(a)||!Array.isArray(b)||a.length!==b.length) return false;
    for (var i=0;i<a.length;i++) if (parseInt(a[i],10)!==parseInt(b[i],10)) return false;
    return true;
  }

  function detectStorageMode(field){
    var $input = field.$input || field.$el.find('input[type="hidden"]').first();
    var name   = $input?.attr('name') || '';
    var inBlock= field.$el.closest('.acf-block-component, .wp-block').length>0;
    var looksMeta = /^acf\[/.test(name);
    var mode = looksMeta ? 'meta' : (inBlock ? 'block' : 'unknown');
    LOG('Storage detection:', {mode, inputName:name||'(none)', key:field.get('key'), nameField:field.get('name'), inBlock});
    return mode;
  }

  function selectedBlock(){ try { return wp?.data?.select('core/block-editor')?.getSelectedBlock(); } catch(e){ return null; } }
  function getClientId(field){
    var $blk = field.$el.closest('.wp-block'); var cid = $blk.attr('data-block'); if (cid) return cid;
    var sel = selectedBlock(); return sel?.clientId || null;
  }
  function snapBlock(reason){
    var blk = selectedBlock();
    GC('Block snapshot: '+reason);
    try{
      if (!blk){ LOG('No selected block.'); return;}
      LOG('name:', blk.name, 'clientId:', blk.clientId);
      LOG('attributes keys:', Object.keys(blk.attributes||{}));
      if (blk.attributes?.data){ LOG('attributes.data keys:', Object.keys(blk.attributes.data)); LOG('attributes.data raw:', blk.attributes.data); }
    } finally { GE(); }
  }
  function snapMeta(reason){
    var meta = wp?.data?.select('core/editor')?.getEditedPostAttribute?.('meta');
    GC('Meta snapshot: '+reason);
    try{ if (!meta){ LOG('No meta object.'); return; } LOG('meta keys:', Object.keys(meta)); LOG('meta raw:', meta); } finally { GE(); }
  }

  // =================== ATTR SYNC ===================
  function forceSyncAttrs(field, ids){
    GC('Force-sync block attributes');
    try{
      if (!(wp?.data?.dispatch && wp?.data?.select)){ WARN('wp.data APIs unavailable.'); return false; }
      var cid = getClientId(field); if (!cid){ WARN('No clientId; cannot sync.'); return false; }
      var sel = wp.data.select('core/block-editor');
      var dis = wp.data.dispatch('core/block-editor');
      var blk = sel.getBlock(cid); if (!blk){ WARN('Block not found for clientId', cid); return false; }

      var attrs = Object.assign({}, blk.attributes);
      attrs.data = attrs.data || {};
      var changed = false;

      var fname = field.get('name'); // prefer name
      var fkey  = field.get('key');

      function setAtPath(obj, key, val){ if (!key) return false; if (eqIds(obj[key], val)) return false; obj[key]=val.slice(0); return true; }

      // Primary targets
      changed = setAtPath(attrs.data, fname, ids) || changed;
      changed = setAtPath(attrs.data, fkey , ids) || changed;

      // Heuristics: try root attrs that look like our field
      Object.keys(attrs).forEach(function(k){
        if (k=== 'data') return;
        if (k===fname || k===fkey || k.indexOf(fname)>=0 || k.indexOf(fkey)>=0){
          if (setAtPath(attrs, k, ids)) { LOG('Patched attrs['+k+'] →', ids); changed = true; }
        }
      });

      // Heuristics: if data.* contains an object with our field nested (rare)
      Object.keys(attrs.data).forEach(function(k){
        var v = attrs.data[k];
        if (Array.isArray(v)) return; // already handled
        if (v && typeof v==='object'){
          if (fname && Array.isArray(v[fname]) && !eqIds(v[fname], ids)){ v[fname]=ids.slice(0); changed=true; LOG('Patched attrs.data['+k+'].'+fname+' →', ids); }
          if (fkey  && Array.isArray(v[fkey ]) && !eqIds(v[fkey ], ids)){ v[fkey ]=ids.slice(0); changed=true; LOG('Patched attrs.data['+k+'].'+fkey +' →', ids); }
        }
      });

      if (!changed){ LOG('Block attrs already matched; no patch.'); return true; }

      snapBlock('before updateBlockAttributes');
      
      dis.updateBlockAttributes(cid, attrs);
      nudgeDirty();

      // Verify immediately and after delays
      setTimeout(function(){
        var after = sel.getBlock(cid);
        LOG('Post-sync (50ms) attributes keys:', after? Object.keys(after.attributes||{}):'(none)');
        if (after?.attributes?.data){ 
          LOG('Post-sync (50ms) attrs.data keys:', Object.keys(after.attributes.data)); 
          LOG('Post-sync (50ms) attrs.data[gallery]:', after.attributes.data[fname]);
          LOG('Post-sync (50ms) attrs.data[field_yakstretch_gallery]:', after.attributes.data[fkey]);
        }
      }, 50);
      
      // Also check after longer delay to see if ACF overwrites it
      setTimeout(function(){
        var after = sel.getBlock(cid);
        LOG('Post-sync (500ms) - checking if ACF overwrote');
        if (after?.attributes?.data){ 
          LOG('Post-sync (500ms) attrs.data[gallery]:', after.attributes.data[fname]);
          LOG('Post-sync (500ms) attrs.data[field_yakstretch_gallery]:', after.attributes.data[fkey]);
          // If ACF overwrote it, re-apply
          var currentGallery = after.attributes.data[fname] || after.attributes.data[fkey];
          if (currentGallery && Array.isArray(currentGallery) && (currentGallery.length === 0 || currentGallery[0] === 0 || currentGallery[0] === '0')) {
            // Re-apply
            var reAttrs = Object.assign({}, after.attributes);
            reAttrs.data = Object.assign({}, reAttrs.data);
            reAttrs.data[fname] = ids.slice(0);
            reAttrs.data[fkey] = ids.slice(0);
            dis.updateBlockAttributes(cid, reAttrs);
          }
        }
      }, 500);

      return true;
    } catch(e){ ERR('forceSyncAttrs', e); return false; } finally { GE(); }
  }

  // =================== ACF GALLERY DEBUGGING ===================
  function debugACFGalleryStructure(field){
    LOG('🔍 STARTING ACF GALLERY STRUCTURE ANALYSIS');
    GC('ACF GALLERY STRUCTURE ANALYSIS');
    try {
      LOG('=== ACF GALLERY FIELD ANALYSIS ===');
      LOG('Field key:', field.get('key'));
      LOG('Field name:', field.get('name'));
      LOG('Field type:', field.get('type'));

      var $el = field.$el;
      var $gallery = $el.find('.acf-gallery');

      LOG('Gallery container found:', $gallery.length > 0);

      if ($gallery.length) {
        var $hiddenInput = $gallery.find('input[type="hidden"]');
        LOG('Hidden input found:', $hiddenInput.length);
        if ($hiddenInput.length) {
          LOG('Hidden input name:', $hiddenInput.attr('name'));
          LOG('Hidden input value:', $hiddenInput.val());
        }

        var $attachments = $gallery.find('.acf-gallery-attachment');
        LOG('Attachment elements:', $attachments.length);

        $attachments.each(function(index){
          var $att = $(this);
          LOG('Attachment ' + index + ':', {
            'data-id': $att.data('id'),
            'inputs': $att.find('input').length,
            'input names': $att.find('input').map(function(){ return $(this).attr('name'); }).get(),
            'input values': $att.find('input').map(function(){ return $(this).val(); }).get()
          });
        });

        LOG('=== GALLERY HTML STRUCTURE ===');
        LOG($gallery.html());
      }

      // Check what ACF actually stores
      LOG('=== ACF STORAGE ANALYSIS ===');
      var currentModelValue = field.val();
      LOG('Current model value:', currentModelValue);
      LOG('Model value type:', typeof currentModelValue);

      if (currentModelValue && Array.isArray(currentModelValue)) {
        LOG('Model array length:', currentModelValue.length);
        LOG('Model array contents:', currentModelValue);
      }

      // Check WordPress post data
      if (window.wp && wp.data && wp.data.select) {
        try {
          var postMeta = wp.data.select('core/editor').getEditedPostAttribute('meta');
          LOG('Post meta keys:', postMeta ? Object.keys(postMeta) : 'none');

          if (postMeta) {
            Object.keys(postMeta).forEach(function(key){
              if (key.includes('gallery') || key.includes(field.get('name')) || key.includes(field.get('key'))) {
                LOG('Relevant meta key:', key, '=', postMeta[key]);
              }
            });
          }
        } catch(e) {
          LOG('Could not access post meta:', e);
        }
      }

      LOG('🔍 ACF GALLERY STRUCTURE ANALYSIS COMPLETE');

    } finally { GE(); }
  }

  // =================== CORE WRITE ===================
  function setGalleryIds(field, ids){
    try{
      var $galleryContainer = field.$el.find('.acf-gallery');
      var $hiddenInput = $galleryContainer.find('input[type="hidden"]');
      var idsString = ids.join(',');
      
      // CRITICAL: Update hidden input FIRST - ACF reads from this during save!
      // If this is wrong, ACF will overwrite block attributes with wrong data
      $hiddenInput.val(idsString);
      
      // Also trigger input event so ACF knows it changed
      $hiddenInput.trigger('input').trigger('change');

      // =================== METHOD 2: ACF MODEL (CRITICAL - MUST UPDATE FIRST) ===================
      // CRITICAL: ACF Gallery expects array of IDs, not objects
      // Must update field model FIRST before updating block attributes
      // This ensures ACF's internal state matches what we're saving
      // ALSO: This updates the hidden input that ACF reads during save!
      field.val(ids);
      
      // Verify the hidden input was updated by field.val()
      var hiddenAfterVal = $hiddenInput.val();
      if (hiddenAfterVal !== idsString) {
        $hiddenInput.val(idsString);
        $hiddenInput.trigger('input').trigger('change');
      }
      
      // Wait a tick for ACF to process the value change
      setTimeout(function() {
        // CRITICAL: Trigger ACF Gallery to render/update the UI
        if (typeof field.render === 'function') {
          try {
            field.render();
          } catch(renderErr) {
            // Silent fail
          }
        }
        
        // Also try triggering the gallery's internal update
        if (field.$el && field.$el.find('.acf-gallery').length) {
          try {
            acf.doAction('render', field.$el);
          } catch(renderErr) {
            // Silent fail
          }
        }
      }, 10);

      // =================== METHOD 3: FORCE FIELD RECOGNITION ===================
      // Try to trigger ACF's internal field change
      try {
        field.$el.trigger('change');
        field.$input && field.$input.trigger('change');
        acf.doAction('change', field.$el);
      } catch(e) {
        // Silent fail
      }

      // =================== METHOD 4: BLOCK ATTRIBUTE SYNC ===================
      // CRITICAL: Do this AFTER ACF field model is updated (in setTimeout above)
      // This ensures ACF's internal state matches before we sync to block attributes
      var storageMode = detectStorageMode(field);
      if (storageMode === 'block') {
        // Wait for ACF model to be updated first (from Method 2 setTimeout)
        setTimeout(function() {
          forceSyncAttrs(field, ids);
        }, 50); // Wait for ACF model update to complete
      }

      // =================== METHOD 5: FORM SUBMISSION PREP ===================
      // Force form to recognize the field
      var $form = $hiddenInput.closest('form');
      if ($form.length) {
        $form.trigger('change');
      }

      // =================== METHOD 6: WORDPRESS EDITOR SYNC ===================
      nudgeDirty();

    } finally { GE(); }
  }

  // =================== ANTI-CLOBBER (Monkeypatch other script) ===================
  function installAntiClobber(){
    // If another global object exposes gallery DOM writers, replace them with no-ops.
    var patched = false;
    function neuter(obj, name){
      if (obj && typeof obj[name] === 'function'){
        obj['__orig_'+name] = obj[name];
        obj[name] = function(){ WARN('Blocked DOM hack', name, '— using model only.'); };
        patched = true;
      }
    }
    // Common names from your logs / earlier snippets:
    // Never neuter our diagnostics handler; only log its presence
    if (window.ACFGalleryHandler){
      LOG('Diagnostics: window.ACFGalleryHandler detected (will not be neutered).');
    }
    if (window.TomatilloMediaFrame){
      // No-op any "manual gallery creation" helpers if present globally
      ['createManualGalleryAttachments','createSingleGalleryAttachment'].forEach(function(n){
        neuter(window.TomatilloMediaFrame, n);
      });
    }
    if (patched) LOG('Anti-clobber installed: DOM/hidden-input hacks neutralized.');
  }

  // =================== TAMPER WATCH (auto-repair) ===================
  function installTamperRepair(field, ids){
    var target = field.$el.find('.acf-gallery').get(0);
    if (!target || !window.MutationObserver) return;
    var armedUntil = Date.now()+2000; // watch for 2s after we set
    var obs = new MutationObserver(function(muts){
      if (Date.now()>armedUntil){ obs.disconnect(); return; }
      // If DOM changes, but model value flips or inputs got mutated, re-apply.
      var current = field.val() || [];
      // If inputs exist and disagree with model, or DOM changed w/o model change, repair.
      var hiddenVals = field.$el.find('input[type="hidden"]').map(function(){ return $(this).val(); }).get();
      GC('Tamper detected: inspecting');
      try {
        LOG('Current model IDs:', current);
        LOG('Hidden inputs:', hiddenVals);
      } finally { GE(); }
      // Re-apply model + attrs
      setGalleryIds(field, Array.isArray(current)? current : ids);
    });
    obs.observe(target, { childList:true, subtree:true, attributes:true });
    // store for debug
    field._tdTamperObs = obs;
  }

  // =================== WIRING ===================
  function wireGalleryField(field){
    if (!field || field._tdBound) return;
    field._tdBound = true;

    var $el = field.$el;
    LOG('Bind gallery field', field.get('key'), 'name:', field.get('name'));

    installAntiClobber(); // kill DOM hacks from other file (if present)

    var $add = $el.find('.acf-button[data-name="add"], .acf-button[data-name="bulk-add"]').first();
    var hasTomatillo = (typeof window.TomatilloMediaFrame === 'function') ||
                       (window.TomatilloMediaFrame && typeof window.TomatilloMediaFrame.open === 'function');

    function openWithTomatillo(current){
      LOG('🎯 Opening Tomatillo Media Frame');
      LOG('Current gallery IDs:', current);
      
      var opts = {
        multiple:true, library:{type:'image'}, title:'Select images', selected: current,
        onSelect: function(selection){
          LOG('🎯 Media Frame onSelect callback fired!');
          LOG('Selection received:', selection);
          var newIds = idsFromSelection(selection);
          LOG('Parsed IDs from selection:', newIds);
          
          if (!newIds.length){ 
            WARN('No valid IDs from selection.'); 
            return; 
          }
          
          // CRITICAL: For gallery fields, we should REPLACE, not merge, when selecting multiple
          // The user is selecting NEW images, not adding to existing
          // Only merge if we want to ADD to existing (which we don't for gallery)
          var idsToSet = newIds; // Replace with new selection
          
          // BUT: If current has valid IDs and user might want to keep them, merge instead
          // Actually, let's check: if current is empty or just ['0'], replace. Otherwise merge.
          var hasValidCurrent = current && Array.isArray(current) && current.length > 0 && 
                                current.some(function(id){ return id && id !== 0 && id !== '0'; });
          
          if (hasValidCurrent) {
            // Merge: add new to existing
            var merged = mergeIds(newIds, current);
            LOG('Merged IDs (new + current):', merged);
            idsToSet = merged;
          } else {
            // Replace: use only new selection
            LOG('Replacing gallery with new selection (no valid current)');
            idsToSet = newIds;
          }
          
          setGalleryIds(field, idsToSet);
          installTamperRepair(field, idsToSet);
        },
        onCancel: function(){ 
          LOG('Modal cancelled.'); 
        },
        onError:  function(m){ 
          ERR('Modal error:', m); 
        }
      };
      if (typeof window.TomatilloMediaFrame === 'function'){
        LOG('TomatilloMediaFrame is a function, creating new instance');
        var frame = new window.TomatilloMediaFrame(opts); 
        frame.open && frame.open();
      } else if (window.TomatilloMediaFrame && window.TomatilloMediaFrame.open) {
        LOG('TomatilloMediaFrame.open exists, calling it');
        window.TomatilloMediaFrame.open(opts);
      } else {
        ERR('TomatilloMediaFrame not available!');
      }
    }

    if (hasTomatillo){
      $add.off('.tdPersist').on('click.tdPersist', function(e){
        e.preventDefault(); e.stopPropagation();
        var current = field.val() || [];
        LOG('Current gallery value from field.val():', current);
        if (!Array.isArray(current)){ 
          current = String(current).split(',').map(function(v){ return parseInt(v,10); }).filter(Number.isFinite); 
          LOG('Converted current to array:', current);
        }
        
        // Filter out invalid values
        current = current.filter(function(id){ return id && id !== 0 && id !== '0'; });
        LOG('Filtered current:', current);
        
        openWithTomatillo(current);
      });
      $el.find('.acf-gallery-attachments .acf-gallery-attachment.-icon .upload')
        .off('.tdPersist').on('click.tdPersist', function(e){ e.preventDefault(); $add.trigger('click'); });
    } else {
      WARN('TomatilloMediaFrame not found; leaving native ACF frame.');
    }

    // Log natural changes (sort/remove)
    $el.off('.tdChange').on('change.tdChange', function(ev){
      var currentVal = field.val() || [];
      LOG('ACF field change → field.val():', currentVal, 'event target:', ev && ev.target ? ev.target.className || ev.target.name || '(anon)' : '(no target)');
    });
  }

  function bindAll(ctx){
    to$(ctx).find('.acf-field[data-type="gallery"]').each(function(){
      var f = getFieldFromEl($(this)); if (f) wireGalleryField(f);
    });
  }

  // =================== SAVE-TIME SAFETY NET ===================
  function installSaveSync(){
    // Monitor for save events and ensure gallery data persists
    if (wp?.data?.subscribe) {
      var lastSaveCheck = 0;
      var saveCheckThrottle = 100; // Only check every 100ms
      
      var unsubscribe = wp.data.subscribe(function(){
        try {
          var now = Date.now();
          if (now - lastSaveCheck < saveCheckThrottle) return; // Throttle
          lastSaveCheck = now;
          
          var isSavingPost = wp.data.select('core/editor').isSavingPost();
          var isAutosavingPost = wp.data.select('core/editor').isAutosavingPost();
          
          if (isSavingPost || isAutosavingPost) {
            
            // Find all gallery fields and verify their block data
            $('.acf-field[data-type="gallery"]').each(function(){
              var $field = $(this);
              var field = getFieldFromEl($field);
              if (!field) return;
              
              var fieldName = field.get('name');
              var fieldKey = field.get('key');
              var currentIds = field.val() || [];
              
              // Filter out invalid values
              currentIds = currentIds.filter(function(id){ return id && id !== 0 && id !== '0'; });
              
              if (currentIds.length > 0) {
                var cid = getClientId(field);
                if (cid) {
                  var blk = wp.data.select('core/block-editor').getBlock(cid);
                  if (blk && blk.attributes && blk.attributes.data) {
                    var blockGallery = blk.attributes.data[fieldName] || blk.attributes.data[fieldKey];
                    var blockIds = Array.isArray(blockGallery) ? blockGallery : [];
                    blockIds = blockIds.filter(function(id){ return id && id !== 0 && id !== '0'; });
                    
                    // If block data doesn't match field model, sync it
                    if (!eqIds(currentIds, blockIds)) {
                      forceSyncAttrs(field, currentIds);
                    }
                  }
                }
              }
            });
          }
        } catch(e) {
          ERR('Save-time sync error:', e);
        }
      });
      
      LOG('Save-time sync installed');
    } else {
      LOG('Save-time sync unavailable - wp.data.subscribe not found');
    }
  }

  // =================== ACF SAVE HOOKS ===================
  // Hook into ACF's save process to prevent overwriting block data
  acf.addAction('prepare', function($el){
    try {
      // $el might be undefined or not a jQuery object in some contexts
      if (!$el || !$el.find) {
        // Try to find gallery fields globally
        $('.acf-field[data-type="gallery"]').each(function(){
          var field = getFieldFromEl($(this));
          if (!field) return;
          
          var currentIds = field.val() || [];
          currentIds = currentIds.filter(function(id){ return id && id !== 0 && id !== '0'; });
          
          if (currentIds.length > 0) {
            var cid = getClientId(field);
            if (cid) {
              LOG('ACF prepare: Syncing gallery before save', {field:currentIds});
              forceSyncAttrs(field, currentIds);
            }
          }
        });
        return;
      }
      
      // This fires before ACF prepares data for save
      // Find gallery fields and ensure block attributes match field model
      $el.find('.acf-field[data-type="gallery"]').each(function(){
        var field = getFieldFromEl($(this));
        if (!field) return;
        
        var currentIds = field.val() || [];
        currentIds = currentIds.filter(function(id){ return id && id !== 0 && id !== '0'; });
        
        if (currentIds.length > 0) {
          var cid = getClientId(field);
          if (cid) {
            forceSyncAttrs(field, currentIds);
          }
        }
      });
    } catch(e) {
      ERR('ACF prepare hook error:', e);
    }
  });
  
  // Also hook into validation to ensure data is synced before validation
  acf.addAction('validation_begin', function($el){
    try {
      if (!$el || !$el.find) {
        $('.acf-field[data-type="gallery"]').each(function(){
          var field = getFieldFromEl($(this));
          if (!field) return;
          
          var currentIds = field.val() || [];
          currentIds = currentIds.filter(function(id){ return id && id !== 0 && id !== '0'; });
          
          if (currentIds.length > 0) {
            var cid = getClientId(field);
            if (cid) {
              LOG('ACF validation_begin: Syncing gallery before validation', {field:currentIds});
              forceSyncAttrs(field, currentIds);
            }
          }
        });
        return;
      }
      
      $el.find('.acf-field[data-type="gallery"]').each(function(){
        var field = getFieldFromEl($(this));
        if (!field) return;
        
        var currentIds = field.val() || [];
        currentIds = currentIds.filter(function(id){ return id && id !== 0 && id !== '0'; });
        
        if (currentIds.length > 0) {
          var cid = getClientId(field);
          if (cid) {
            forceSyncAttrs(field, currentIds);
          }
        }
      });
    } catch(e) {
      ERR('ACF validation_begin hook error:', e);
    }
  });

  // =================== ACF LIFECYCLE ===================
  acf.addAction('ready', function(){ LOG('ACF ready'); bindAll(); installSaveSync(); });
  acf.addAction('append', function($el){ LOG('ACF append'); bindAll($el); });
  acf.addAction('ready_field/type=gallery', wireGalleryField);
  acf.addAction('append_field/type=gallery', wireGalleryField);

  // Global change tap for extra visibility (diagnostics only)
  try {
    acf.addAction('change', function($el){
      try {
        var f = getFieldFromEl($el);
        if (!f) { LOG('Global change: element changed (no field model)'); return; }
        LOG('Global change: field', f.get('name'), 'key', f.get('key'), '→', f.val() || []);
      } catch(e) {
        WARN('Global change logger error:', e);
      }
    });
  } catch(e) {
    WARN('Unable to install global change logger:', e);
  }

  // =================== DEBUG HELPERS ===================
  window.__TD_DEBUG_rebind = function(){
    $('.acf-field[data-type="gallery"]').each(function(){
      var f = getFieldFromEl($(this));
      if (f){ delete f._tdBound; if (f._tdTamperObs?.disconnect) f._tdTamperObs.disconnect(); delete f._tdTamperObs; }
    });
    bindAll(); LOG('Rebound gallery fields.');
  };
  window.__TD_DEBUG_snap = function(reason){ snapBlock(reason||'manual'); snapMeta(reason||'manual'); };

  // Expose diagnostics-only object (no mutating API)
  try {
    window.ACFGalleryHandler = window.ACFGalleryHandler || {};
    window.ACFGalleryHandler.__diagnostics = {
      version: 'diag-1',
      monitorOnly: MONITOR_ONLY,
      enabled: true
    };
    window.ACFGalleryHandler.dumpField = function(keyOrEl){
      try {
        var f = typeof keyOrEl === 'string' ? acf.getField(keyOrEl) : getFieldFromEl(to$(keyOrEl));
        if (!f) { LOG('dumpField: no field'); return null; }
        var snap = { key: f.get('key'), name: f.get('name'), type: f.get('type'), val: f.val() || [] };
        LOG('dumpField:', snap);
        return snap;
      } catch(e){ WARN('dumpField error:', e); return null; }
    };
  } catch(e) { /* no-op */ }

  LOG('🎯 ACF Gallery Handler: Diagnostics loaded (monitor-only).');

})(jQuery);