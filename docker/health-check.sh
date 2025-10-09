#!/bin/bash

# Health check script for Laravel application
curl -f http://localhost/health || exit 1