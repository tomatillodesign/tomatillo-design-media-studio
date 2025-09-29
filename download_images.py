#!/usr/bin/env python3
"""
Download 50 high-resolution nature images from Unsplash for testing
"""

import requests
import json
import os
import time
from urllib.parse import urlparse

# Unsplash API credentials
ACCESS_KEY = "18956574fe56b72c1d546e07f58afde74c383a314bc49cde568568a558b11b44"
SECRET_KEY = "b4906be34e0834a9cceb895281f1d7654cd94a689af0f6b68edb41c379774546"
APP_ID = "28529"

# API endpoints
BASE_URL = "https://api.unsplash.com"
SEARCH_URL = f"{BASE_URL}/search/photos"

# Headers for API requests
headers = {
    "Authorization": f"Client-ID {ACCESS_KEY}",
    "Accept-Version": "v1"
}

def download_image(url, filename, folder):
    """Download an image from URL to specified folder"""
    try:
        response = requests.get(url, stream=True, timeout=30)
        response.raise_for_status()
        
        filepath = os.path.join(folder, filename)
        
        with open(filepath, 'wb') as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)
        
        print(f"✓ Downloaded: {filename}")
        return True
        
    except Exception as e:
        print(f"✗ Failed to download {filename}: {str(e)}")
        return False

def get_nature_images(count=50):
    """Search for nature images on Unsplash"""
    params = {
        "query": "nature",
        "per_page": min(count, 30),  # Unsplash max per page is 30
        "orientation": "landscape",
        "order_by": "relevant"
    }
    
    all_images = []
    page = 1
    
    while len(all_images) < count:
        params["page"] = page
        
        try:
            print(f"Fetching page {page}...")
            response = requests.get(SEARCH_URL, headers=headers, params=params)
            response.raise_for_status()
            
            data = response.json()
            results = data.get("results", [])
            
            if not results:
                print("No more images found")
                break
            
            all_images.extend(results)
            print(f"Found {len(results)} images on page {page}")
            
            page += 1
            time.sleep(1)  # Rate limiting
            
        except Exception as e:
            print(f"Error fetching page {page}: {str(e)}")
            break
    
    return all_images[:count]

def main():
    # Create download folder
    download_folder = os.path.expanduser("~/Desktop/tomatillo-test-images")
    os.makedirs(download_folder, exist_ok=True)
    
    print(f"Downloading 50 nature images to: {download_folder}")
    print("=" * 50)
    
    # Get images from Unsplash
    images = get_nature_images(50)
    
    if not images:
        print("No images found!")
        return
    
    print(f"\nFound {len(images)} images. Starting downloads...")
    print("=" * 50)
    
    successful_downloads = 0
    
    for i, image in enumerate(images, 1):
        # Get the highest resolution URL
        urls = image.get("urls", {})
        download_url = urls.get("raw") or urls.get("full") or urls.get("regular")
        
        if not download_url:
            print(f"✗ No download URL for image {i}")
            continue
        
        # Generate filename
        image_id = image.get("id", f"image_{i}")
        filename = f"nature_{i:02d}_{image_id}.jpg"
        
        # Download the image
        if download_image(download_url, filename, download_folder):
            successful_downloads += 1
        
        # Rate limiting - be nice to Unsplash
        time.sleep(0.5)
    
    print("=" * 50)
    print(f"Download complete!")
    print(f"Successfully downloaded: {successful_downloads}/{len(images)} images")
    print(f"Images saved to: {download_folder}")

if __name__ == "__main__":
    main()
