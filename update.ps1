
$ErrorActionPreference = "Stop"

Set-Location "C:\xampp\htdocs\Bookmyticket_web"

Write-Host "`n=== BookMyTicket: GitHub + Render Update ===" -ForegroundColor Cyan

# Confirm this is a Git repository
git rev-parse --is-inside-work-tree
if ($LASTEXITCODE -ne 0) {
    throw "This folder is not a Git repository."
}

# Show changes before uploading
git status --short

# Stage all changes Git is allowed to track
git add -A
if ($LASTEXITCODE -ne 0) {
    throw "Could not stage files."
}

# Stop if there is nothing to commit
git diff --cached --quiet
if ($LASTEXITCODE -eq 0) {
    Write-Host "No new changes to commit." -ForegroundColor Yellow
    Write-Host "Checking whether your branch is already pushed..."
} else {
    # Ask for a commit message
    $message = Read-Host "Enter update message (e.g. Fix homepage)"
    if ([string]::IsNullOrWhiteSpace($message)) {
        $message = "Update BookMyTicket website"
    }

    git commit -m $message
    if ($LASTEXITCODE -ne 0) {
        throw "Commit failed. Check the Git output."
    }
}

# Push the current branch to GitHub
$branch = git branch --show-current
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($branch)) {
    throw "Could not determine the current Git branch."
}

git push -u origin $branch
if ($LASTEXITCODE -ne 0) {
    throw "Push failed. Resolve the Git error before retrying."
}

Write-Host "`nSuccessfully pushed changes to GitHub!" -ForegroundColor Green
Write-Host "Repository: https://github.com/Patelsai2305/BookMyTickets"
Write-Host "Render should deploy automatically if Auto-Deploy is enabled."
Write-Host "Check deployment: https://dashboard.render.com/web/srv-dadquemq1p3s73erhm9g"
Write-Host "Live website: https://bookmytickets-1.onrender.com"
