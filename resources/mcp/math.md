
## How the numbers work (exact math of the server; use it to explain, do not recompute what tools return)

Day and intake
- A day is a local date in the profile timezone that starts at the day start hour (with 4, food at 01:00 belongs to
  the previous day). Times are stored in UTC.
- Intake of a day = sum of `kcal_max` of its meals (deleted meals do not count). Items with `product_id` are computed
  by the server from the product (per 100 g, or `piece_g` x pieces) and summed into the meal.
- A day without any meal is "not logged", not "0 kcal": it is left out of averages, TDEE and the period budget.

Weight trend
- Several weigh-ins on one day are averaged first. Trend = exponential moving average with alpha {trend_alpha}:
  trend = trend + a * (weight - trend), where a = 1 - (1 - {trend_alpha})^g and g = days since the previous weigh-in.
  Days without a weigh-in keep the last trend value.
- So one weigh-in moves the trend by {trend_alpha_pct}% of the gap: a 1-2 kg water swing barely moves it, and a real change
  shows up in the trend with a lag of about {trend_lag} days. Always talk about the trend, not about single weigh-ins.
- "Change over a week" = trend today - trend 7 days ago.

TDEE (calories per day that keep the weight stable), recalculated on checkpoints every {recalc_every_days} days counted
from the first day with any data (meal or weigh-in); it stays constant between checkpoints
- First {warmup_days} days: start estimate = manual `start_tdee`, or Mifflin-St Jeor (male formula):
  BMR = 10 x trend weight + 6.25 x height_cm - 5 x age + 5, TDEE = BMR x activity_factor.
  No weight, height or age and no `start_tdee` -> TDEE and norm are unknown (null).
- After that, on each checkpoint: window = the last N complete days before it, N = min({window_days}, days since start).
  fact = average kcal_max over logged days of the window - (trend at window end - trend at window start) x {kcal_per_kg} / N.
  New TDEE = {keep_old} x previous TDEE + {keep_new} x fact (smoothed, so one odd week does not swing it).
- If more than {max_missing_pct}% of the window days have no food records (or there is no weight trend), the window is not
  trusted: the previous TDEE is kept and marked `tdee_reliable: false`.
- Consequences to explain when asked: logging only part of the food makes fact TDEE (and the norm) too low; a
  systematic overestimate of food also raises fact TDEE, so it cancels out; 1 kg of trend = {kcal_per_kg} kcal.

Norm
- Norm of a day = max(minimum norm of that day, TDEE of that day x (1 - deficit % of that day / 100)).
- Deficit and minimum norm are dated: a change applies from the day it is made, past days keep the norm they had.
  Height, age, activity factor and start TDEE are not dated: changing them recomputes the whole history.
- Expected pace: (TDEE - eaten) x 7 / {kcal_per_kg} kg per week, e.g. 500 kcal/day below TDEE = about 0.45 kg/week.

`get_progress` (window = the last N complete days, today is not included)
- `intake.avg_kcal` = average kcal_max over logged days of the window; `logged_days` = how many days had food.
- `intake.budget` = the period as a whole: `norm_kcal` = sum of the daily norms of the logged days, `eaten_kcal` = what
  was eaten on them, `over_kcal` = eaten - norms (positive = ate more than allowed over the period, negative = below).
  This is the answer to "did I overeat this month?", better than counting single days over the norm.
- `weight.pace_kg_per_week` = (trend today - trend at the window start) / N x 7; null when the first weigh-in is
  inside the window (no trend at its start yet).
- Flags: too_fast = trend fell more than 1 kg in each of the last two weeks; stalled = trend moved less than 0.2 kg over
  21 days while the average intake of the last 21 days (logged days) was below 95% of TDEE; low_logging = fewer than
  14 meals over the last 7 complete days; below_min = average intake below the minimum norm.
- The web app paints a day orange when it is up to 150 kcal over the norm (within the estimate error) and red above that.
