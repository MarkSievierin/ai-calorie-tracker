# Steps from an iPhone (Apple Health) without an app

Apple Health data cannot be pulled by a server. The phone pushes it: a Shortcuts automation reads the day's step
count and sends it to `PUT /api/metrics`, which sets the value for that day (repeat runs do not create duplicates).

## The shortcut ("Steps to Kkal")

1. **Find Health Samples**: Type is Steps, Start Date is today. Sort and limit stay off.
2. **Calculate Statistics**: Sum of *Health Samples*.
3. **Format Date**: *Current Date*, format Custom `yyyy-MM-dd`.
4. **Get Contents of URL**:
   - URL: `https://<your-host>/api/metrics`
   - Method: PUT
   - Headers: `X-Api-Key` = your `KKAL_TOKEN`
   - Request Body: JSON with `type` (Text) = `steps`, `value` (Number) = *Calculation Result*, `date` (Text) = *Formatted Date*

Run it once by hand: iOS asks for access to Health and to the host; the response contains `"metric"` with the value.

## The automation

Shortcuts -> Automation -> New -> Time of Day -> 23:50, Daily, **Run Immediately** -> run "Steps to Kkal".
A second automation earlier in the day (e.g. 15:00) is fine: the later run replaces the value.

A phone that is off at that time skips the day. To fill gaps, duplicate steps 1-4 inside the shortcut for
yesterday: Start Date "is in the last 1 day" does not work for a calendar day, so use *Adjust Date* (subtract 1 day
from Current Date), filter Start Date "is between" start and end of that day, and send its formatted date.

The same pattern works for any Health quantity: send `sleep_h`, `pulse` or another `type`.
The token is stored inside the shortcut on the phone: do not share the shortcut.
