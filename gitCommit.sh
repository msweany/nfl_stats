#!/bin/bash
echo "Enter your commit message:"
read message

git add .
git commit -m "$message"
git push
