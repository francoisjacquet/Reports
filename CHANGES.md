# CHANGES
## Reports module for RosarioSIS

Changes in 1.9
--------------
- CSS style Calculations items as buttons in Calculations.php
- Fix PHP error empty sum(), average(), count(), max(), min() & other functions in ReportCalculations.fnc.php

Changes in 1.8
--------------
- SQL error fix on install: check if exists before INSERT in install.sql

Changes in 1.7
--------------
- Escape SQL identifiers in Calculations.php & SavedReports.php

Changes in 1.6
--------------
- Correctly parse multi-language field Title in Calculations.php
- Update English help in Help_en.php
- Remove add button when clicked in functions.js
- Correctly localize "Save Report" in functions.php
- Add Spanish & French translations + Help

Changes in 1.5
--------------
- Fix checkboxes to be compatible with RosarioSIS 2.9.12+

Changes in 1.4
--------------
- SQL fix: use ISO date format for timespan in ReportsCalculations.fnc.php

Changes in 1.3
--------------
- Compatibility with RosarioSIS 2.9 in SavedReports.php
- Use Bottom.php|bottom_buttons hook for Save Report button in functions.php

Changes in 1.2
--------------
- Adjust Search screens position and reset Equation on Save in Calculations.php & functions.js
- Add equation to Calculation Title in CalculationsReports.php
- Fix search screens, Age breakdown, _makeURL() & _makeScreens() in ReportsCalculations.fnc.php

Changes in 1.1
--------------
- Add Age breakdown in Calculations.php & CalculationsReports.php
- Fix breakdown results in ReportsCalculations.fnc.php
- Add "No Value" to select fields in ReportsCalculations.fnc.php
- Added English help texts in Help_en.php
