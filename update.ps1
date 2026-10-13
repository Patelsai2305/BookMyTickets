
$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

Write-Host "`n=== BookMyTicket Auto Update ===" -ForegroundColor Cyan

# Check Git status
git status

# Ask for a commit message
$message = Read-Host "Enter update message (press Enter for default)"
if ([string]::IsNullOrWhiteSpace($message)) {
    $message = "Update BookMyTicket website"
}

# Stage all project changes
git add .
if ($LASTEXITCODE -ne 0) { throw "Git add failed." }

# Commit changes if there are any
git diff --cached --quiet
if ($LASTEXITCODE -eq 1) {
    git commit -m $message
    if ($LASTEXITCODE -ne 0) { throw "Git commit failed." }
} elseif ($LASTEXITCODE -eq 0) {
    Write-Host "No new changes to commit."
} else {
    throw "Could not check staged changes."
}

# Push to GitHub
git push
if ($LASTEXITCODE -ne 0) { throw "Git push failed. Check the error above." }

Write-Host "`nGitHub updated successfully!" -ForegroundColor Green
Write-Host "Render should deploy automatically if Auto-Deploy is enabled." -ForegroundColor Green
