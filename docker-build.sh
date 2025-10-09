#!/bin/bash

# Stop and remove existing container if it exists
echo "Cleaning up existing container..."
docker stop laravel-inventory 2>/dev/null || true
docker rm laravel-inventory 2>/dev/null || true

# Build the Docker image
echo "Building Docker image..."
docker build -t laravel-inventory-app .

if [ $? -ne 0 ]; then
    echo "Docker build failed. Please check the error messages above."
    exit 1
fi

# Run the container
echo "Starting container..."
docker run -d \
  --name laravel-inventory \
  -p 8080:80 \
  -v $(pwd)/storage:/var/www/html/storage \
  -v $(pwd)/database:/var/www/html/database \
  laravel-inventory-app

if [ $? -eq 0 ]; then
    echo "✅ Application is running at http://localhost:8080"
    echo "To stop the container: docker stop laravel-inventory"
    echo "To remove the container: docker rm laravel-inventory"
    echo "To view logs: docker logs laravel-inventory"
else
    echo "❌ Failed to start container. Check Docker logs for details."
    exit 1
fi