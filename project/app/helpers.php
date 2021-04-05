<?php

if (!function_exists('weekDaysNumberStartFrom')) {
    /**
     * Gets a week day number and
     * returns all the week days numbers starting from the given day
     *
     * @param int $start_day
     * @return array
     */
    function weekDaysNumberStartFrom(int $start_day): array
    {
        $ranges = array_merge(range($start_day, 6), range(0, $start_day - 1));

        return array_splice($ranges, 0, 7);
    }
}

if (!function_exists('dayPlan')) {
    /**
     * Returns a list of open and close hours in a given day
     *
     * @param \Illuminate\Support\Collection $day_times
     * @param string $start_time
     * @param string $end_time
     * @return \Illuminate\Support\Collection
     */
    function dayPlan(\Illuminate\Support\Collection $day_times, string $start_time = '00:00', string $end_time = '24:00'): \Illuminate\Support\Collection
    {
        list($start_hour, $start_minute) = explode(':', $start_time);
        list($end_hour, $end_minute) = explode(':', $end_time);

        $start_timestamp = mktime($start_hour, $start_minute, 0);
        $end_timestamp = mktime($end_hour, $end_minute, 0);

        $output = collect([]);
        $keys = ['from', 'to', 'status', 'day'];
        $day = $day_times->first()['day'] ?? null;

        foreach ($day_times as $time) {
            $time = collect($time);
            $interval = [];
            list($from_hour, $from_minute) = explode(':', $time['from']);
            $from_timestamp = mktime($from_hour, $from_minute, 0);

            list($to_hour, $to_minute) = explode(':', $time['to']);
            $to_timestamp = mktime($to_hour, $to_minute, 0);

            $interval['from'] = $start_timestamp < $from_timestamp ? $start_time : $time['from'];
            $interval['to'] = $end_timestamp < $from_timestamp ? $end_time : $time['from'];
            $interval['status'] = 0;
            $interval['day'] = $day;

            if ($interval['from'] !== $interval['to']) {
                $output->push($interval);
            }
            $output->push($time->only($keys));

            $start_timestamp = $to_timestamp;
            $start_time = $time['to'];
        }

        if ($start_timestamp < $end_timestamp) {
            $output->push(
                [
                    'from' => $start_time,
                    'to' => $end_time,
                    'status' => 0,
                    'day' => $day
                ]
            );
        }

        return $output;
    }
}
