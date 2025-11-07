#!/usr/bin/env python3
"""
Download random high-resolution images from Unsplash for testing.

Notes:
- Uses /photos/random (max 30 per call); loops to reach desired count.
- Saves to ~/Desktop/tomatillo-test-images by default.
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
RANDOM_URL = f"{BASE_URL}/photos/random"

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

def get_random_images(count=50, query=None, orientation="landscape"):
    """Fetch random images from Unsplash."""
    images = []
    remaining = count
    batch_index = 1
    while remaining > 0:
        batch = min(remaining, 30)  # Unsplash max count per request
        params = {
            "count": batch,
            "orientation": orientation,
        }
        if query:
            params["query"] = query
        try:
            print(f"Fetching random batch {batch_index} (count={batch})...")
            response = requests.get(RANDOM_URL, headers=headers, params=params, timeout=30)
            response.raise_for_status()
            data = response.json()
            if isinstance(data, dict):
                # When count is omitted, Unsplash returns a single object
                data = [data]
            if not data:
                print("No images returned in this batch")
                break
            images.extend(data)
            remaining -= len(data)
            batch_index += 1
            time.sleep(1)  # Gentle rate limit
        except Exception as e:
            print(f"Error fetching random images: {str(e)}")
            break
    return images[:count]

def main():
    # Create download folder
    download_folder = os.path.expanduser("~/Desktop/tomatillo-test-images")
    os.makedirs(download_folder, exist_ok=True)
    
    print(f"Downloading 50 random images to: {download_folder}")
    print("=" * 50)
    
    # Get images from Unsplash (change query to limit topic if desired)
    images = get_random_images(50, query=None)
    
    if not images:
        print("No images found!")
        return
    
    print(f"\nFound {len(images)} images. Starting downloads...")
    print("=" * 50)
    
    successful_downloads = 0
    
    for i, image in enumerate(images, 1):
        # Get a high-resolution URL
        urls = image.get("urls", {})
        # Prefer 'full' to avoid some RAW query param requirements
        download_url = urls.get("full") or urls.get("raw") or urls.get("regular")
        
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
