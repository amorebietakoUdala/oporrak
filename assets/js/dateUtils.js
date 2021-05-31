/* Calculates the days beetween 2 dates */
export function daysBeetween(startDate, endDate) {
    const date1 = Date.parse(startDate);
    const date2 = Date.parse(endDate);
    const diffTime = Math.abs(date2 - date1);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays + 1;
}

/* Calculates the working days beetween 2 dates. 
   Removes the holidays that are between those 2 dates
   Holidays must have startDate and endDate and hae to be the same. Each element it's only one day.
*/
export function workingDaysBetween(d0, d1, holidays) {
    /* Two working days and an sunday (not working day) */
    var startDate = Date.parse(d0);
    var endDate = Date.parse(d1);

    // Validate input
    if (endDate <= startDate) {
        return 1;
    }

    var startDate = new Date(startDate);
    var endDate = new Date(endDate);
    // Calculate days between dates
    var millisecondsPerDay = 86400 * 1000; // Day in milliseconds
    startDate.setHours(0, 0, 0, 1); // Start just after midnight
    endDate.setHours(23, 59, 59, 999); // End just before midnight
    var diff = endDate - startDate; // Milliseconds between datetime objects    
    var days = Math.ceil(diff / millisecondsPerDay);

    // Subtract two weekend days for every week in between
    var weeks = Math.floor(days / 7);
    days -= weeks * 2;

    // Handle special cases
    var startDay = startDate.getDay();
    var endDay = endDate.getDay();

    // Remove weekend not previously removed.   
    if (startDay - endDay > 1) {
        days -= 2;
    }
    // Remove start day if span starts on Sunday but ends before Saturday
    if (startDay == 0 && endDay != 6) {
        days--;
    }
    // Remove end day if span ends on Saturday but starts after Sunday
    if (endDay == 6 && startDay != 0) {
        days--;
    }
    /* Here is the code */
    holidays.forEach(day => {
        if ((day.startDate >= startDate) && (day.startDate <= endDate)) {
            /* If it is not saturday (6) or sunday (0), substract it */
            if ((day.startDate.getDay() % 6) != 0) {
                days--;
            }
        }
    });
    return days;
}

// export function calculateDays(events) {
//     let days = 0;
//     events.forEach(element => {
//         days += daysBeetween(element.startDate, element.endDate);
//     });
//     return days;
// }