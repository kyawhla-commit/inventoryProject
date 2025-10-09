#!/bin/bash

# Laravel Inventory Docker Management Script

CONTAINER_NAME="laravel-inventory"
IMAGE_NAME="laravel-inventory-app"
PORT="8080"

case "$1" in
    "build")
        echo "🔨 Building Docker image..."
        docker build -t $IMAGE_NAME .
        ;;
    "start")
        echo "🚀 Starting container..."
        docker stop $CONTAINER_NAME 2>/dev/null || true
        docker rm $CONTAINER_NAME 2>/dev/null || true
        docker run -d \
          --name $CONTAINER_NAME \
          -p $PORT:80 \
          -v $(pwd)/storage:/var/www/html/storage \
          -v $(pwd)/database:/var/www/html/database \
          $IMAGE_NAME
        echo "✅ Application is running at http://localhost:$PORT"
        ;;
    "stop")
        echo "🛑 Stopping container..."
        docker stop $CONTAINER_NAME
        ;;
    "restart")
        echo "🔄 Restarting container..."
        docker restart $CONTAINER_NAME
        ;;
    "logs")
        echo "📋 Container logs:"
        docker logs -f $CONTAINER_NAME
        ;;
    "shell")
        echo "🐚 Opening shell in container..."
        docker exec -it $CONTAINER_NAME /bin/bash
        ;;
    "status")
        echo "📊 Container status:"
        docker ps -f name=$CONTAINER_NAME
        ;;
    "clean")
        echo "🧹 Cleaning up..."
        docker stop $CONTAINER_NAME 2>/dev/null || true
        docker rm $CONTAINER_NAME 2>/dev/null || true
        docker rmi $IMAGE_NAME 2>/dev/null || true
        ;;
    "rebuild")
        echo "🔄 Rebuilding and starting..."
        $0 clean
        $0 build
        $0 start
        ;;
    "fix-csrf")
        echo "🔧 Fixing CSRF token issues..."
        docker exec $CONTAINER_NAME php artisan config:clear
        docker exec $CONTAINER_NAME php artisan cache:clear
        docker exec $CONTAINER_NAME php artisan view:clear
        docker exec $CONTAINER_NAME php artisan route:clear
        docker exec $CONTAINER_NAME php artisan key:generate --force
        docker restart $CONTAINER_NAME
        echo "✅ CSRF fix applied. Clear your browser cache and try again."
        ;;
    "fix-db")
        echo "🔧 Fixing database permissions..."
        docker exec $CONTAINER_NAME chown -R www-data:www-data /var/www/html/database
        docker exec $CONTAINER_NAME chmod -R 775 /var/www/html/database
        docker exec $CONTAINER_NAME chmod 664 /var/www/html/database/database.sqlite
        docker exec $CONTAINER_NAME php artisan cache:clear
        echo "✅ Database permissions fixed."
        ;;
    "db")
        echo "📊 Opening database shell..."
        docker exec -it $CONTAINER_NAME sqlite3 /var/www/html/database/database.sqlite
        ;;
    "fix-sqlite")
        echo "🔧 Fixing SQLite compatibility..."
        docker exec $CONTAINER_NAME php artisan config:clear
        docker exec $CONTAINER_NAME php artisan cache:clear
        docker exec $CONTAINER_NAME php artisan view:clear
        echo "✅ SQLite compatibility fixed."
        ;;
    *)
        echo "Laravel Inventory Docker Manager"
        echo "Usage: $0 {build|start|stop|restart|logs|shell|status|clean|rebuild|fix-csrf|fix-db|fix-sqlite|db}"
        echo ""
        echo "Commands:"
        echo "  build      - Build the Docker image"
        echo "  start      - Start the container"
        echo "  stop       - Stop the container"
        echo "  restart    - Restart the container"
        echo "  logs       - View container logs"
        echo "  shell      - Open shell in container"
        echo "  status     - Show container status"
        echo "  clean      - Remove container and image"
        echo "  rebuild    - Clean, build, and start"
        echo "  fix-csrf   - Fix CSRF token issues (419 errors)"
        echo "  fix-db     - Fix database permissions (readonly errors)"
        echo "  fix-sqlite - Fix SQLite compatibility issues"
        echo "  db         - Open SQLite database shell"
        exit 1
        ;;
esac