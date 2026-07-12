# sizes of base vs components
$root = 'C:\xampp\htdocs\Trans-Nzoia-Affordable-Housing\admin\assets\css'
Get-Item "$root\admin-global.css" | Select-Object Name, Length
# count selectors approx in admin-global for card table form modal
$g = Get-Content "$root\admin-global.css" -Raw
foreach ($pat in @('\.card\b','\.data-table','\.form-','\.modal','\.btn','\.badge','@media')) {
  $n = ([regex]::Matches($g, $pat)).Count
  Write-Output "$pat => $n"
}
Write-Output ("admin-global lines: " + ((Get-Content "$root\admin-global.css").Count))
Write-Output ("admin-global size: " + (Get-Item "$root\admin-global.css").Length)
