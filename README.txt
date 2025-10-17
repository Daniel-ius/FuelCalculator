CALCULATOR URLS
---------------

Main Calculator Page:
/fuel-calculator

Calculation History:
/fuel-calculations

Admin Configuration:
/admin/config/system/fuel-calculator

URL Parameter Prefilling:
/fuel-calculator?distance=250&efficiency=7.5&price=1.45

Available URL parameters:
- distance: Distance in kilometers (e.g., 250)
- efficiency: Fuel efficiency in L/100km (e.g., 7.5)
- price: Fuel price per liter in EUR (e.g., 1.45)

Example API Usage:
curl -X POST https://example.com/api/fuel-calculator/calculate?_format=json \
  -H "Content-Type: application/json" \
  -d '{
        "distance": 200,
        "efficiency": 7.5,
        "price": 1.80
      }'