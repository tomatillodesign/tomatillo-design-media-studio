<script type="text/html" id="tmpl-tomatillo-attachments-browser">
    <div class="tomatillo-attachments-header">
        <div class="tomatillo-search-filter">
            <input type="text" class="tomatillo-search-input" placeholder="Search media..." />
            <select class="tomatillo-filter-select">
                <option value="all">All Types</option>
                <option value="image">Images</option>
                <option value="video">Videos</option>
                <option value="audio">Audio</option>
                <option value="application">Documents</option>
            </select>
            <button class="tomatillo-upload-btn">Upload Files</button>
            <input type="file" class="tomatillo-file-input" multiple accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">
        </div>
    </div>
    <div class="tomatillo-attachments-container">
        <div class="tomatillo-attachments-grid">
            <% _.each(collection.models, function(attachment) { %>
                <div class="tomatillo-media-item" data-attachment-id="<%= attachment.id %>">
                    <% if (attachment.get('type') === 'image') { %>
                        <img src="<%= attachment.get('sizes').thumbnail ? attachment.get('sizes').thumbnail.url : attachment.get('url') %>" 
                             class="tomatillo-media-thumbnail" 
                             alt="<%= attachment.get('alt') %>" />
                    <% } else { %>
                        <div class="tomatillo-media-thumbnail tomatillo-file-icon">
                            <span class="dashicons dashicons-<%= attachment.get('type') === 'video' ? 'video-alt3' : attachment.get('type') === 'audio' ? 'format-audio' : 'media-document' %>"></span>
                        </div>
                    <% } %>
                    <div class="tomatillo-media-info">
                        <div class="tomatillo-media-title"><%= attachment.get('title') || attachment.get('filename') %></div>
                        <div class="tomatillo-media-meta">
                            <%= attachment.get('type') %> • 
                            <%= attachment.get('filesizeHumanReadable') || 'Unknown size' %>
                        </div>
                    </div>
                </div>
            <% }); %>
        </div>
        <% if (collection.hasMore()) { %>
            <div class="tomatillo-load-more">
                <button class="button">Load More</button>
            </div>
        <% } %>
    </div>
</script>
