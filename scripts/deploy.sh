#!/bin/bash

set -e

echo "======================================"
echo "Starting Laravel deployment"
echo "======================================"

cd ~/apps/PHPEducation_Backend-main

echo ""
echo "[1/4] Pulling latest source code..."
git pull origin main

echo ""
echo "[2/4] Building Laravel Docker image..."
docker compose build app

echo ""
echo "[3/4] Starting Laravel containers..."
docker compose up -d app

echo ""
echo "[4/4] Checking container status..."
docker compose ps

echo ""
echo "======================================"
echo "Deployment completed successfully!"
echo "======================================"
