-- Drop functions if they exist
DROP FUNCTION IF EXISTS calculate_overdue_days;
DROP FUNCTION IF EXISTS calculate_overdue_penalty_amount;

-- Function to calculate business days between two dates
DELIMITER $$

CREATE FUNCTION calculate_overdue_days(
    start_date DATE,
    end_date DATE,
    is_saturday BOOLEAN,
    is_sunday BOOLEAN
) RETURNS INT
    DETERMINISTIC
    READS SQL DATA
BEGIN
    DECLARE business_days INT DEFAULT 0;
    DECLARE current_date DATE;
    DECLARE day_of_week INT;
    DECLARE is_closed_day BOOLEAN;

    -- Start from the day after start_date
    SET current_date = DATE_ADD(start_date, INTERVAL 1 DAY);

    WHILE current_date <= end_date DO
        SET day_of_week = DAYOFWEEK(current_date); -- 1=Sunday, 7=Saturday
        SET is_closed_day = FALSE;

        -- Check if date is in closed_days table
        IF EXISTS (
            SELECT 1 FROM closed_days
            WHERE date = current_date AND is_active = 1
        ) THEN
            SET is_closed_day = TRUE;
END IF;

        -- Count as business day if:
        -- - Not a closed day
        -- - Not Saturday (day 7) if Saturday is not working day
        -- - Not Sunday (day 1) if Sunday is not working day
        IF NOT is_closed_day
           AND (is_saturday = TRUE OR day_of_week != 7)
           AND (is_sunday = TRUE OR day_of_week != 1) THEN
            SET business_days = business_days + 1;
END IF;

        SET current_date = DATE_ADD(current_date, INTERVAL 1 DAY);
END WHILE;

    -- Subtract 1 as per original logic
RETURN GREATEST(business_days - 1, 0);
END$$

DELIMITER ;

-- Function to calculate overdue penalty amount
DELIMITER $$

CREATE FUNCTION calculate_overdue_penalty_amount(
    is_overdue_penalty BOOLEAN,
    loan_end_date DATE,
    overdue_penalty_paid DECIMAL(15,3),
    penalty_amount DECIMAL(15,3),
    check_date DATE
) RETURNS DECIMAL(15,3)
    DETERMINISTIC
    READS SQL DATA
BEGIN
    DECLARE overdue_days INT DEFAULT 0;
    DECLARE overdue_amount DECIMAL(15,3) DEFAULT 0;
    DECLARE max_days DECIMAL(15,3);
    DECLARE is_saturday BOOLEAN;
    DECLARE is_sunday BOOLEAN;
    DECLARE today DATE;

    SET today = IFNULL(check_date, CURDATE());

    -- If loan hasn't ended yet, no penalty
    IF today <= loan_end_date THEN
        RETURN 0;
END IF;

    -- Get loan settings
SELECT CAST(value AS UNSIGNED) INTO is_saturday
FROM loan_settings
WHERE `key` = 'is_saturday'
    LIMIT 1;

SELECT CAST(value AS UNSIGNED) INTO is_sunday
FROM loan_settings
WHERE `key` = 'is_sunday'
    LIMIT 1;

SELECT CAST(value AS DECIMAL(15,3)) INTO max_days
FROM loan_settings
WHERE `key` = 'max_overdue_penalty_count'
    LIMIT 1;

-- Set defaults if not found
SET is_saturday = IFNULL(is_saturday, 0);
    SET is_sunday = IFNULL(is_sunday, 0);
    SET max_days = IFNULL(max_days, 0);

    -- Calculate business days
    SET overdue_days = calculate_overdue_days(
        loan_end_date,
        today,
        is_saturday,
        is_sunday
    );

    -- Apply max days cap if configured
    IF max_days > 0 AND overdue_days > max_days THEN
        SET overdue_days = max_days;
END IF;

    -- Calculate overdue amount
    SET overdue_amount = (overdue_days * penalty_amount) - overdue_penalty_paid;

    -- Return amount only if overdue penalty is enabled
    IF is_overdue_penalty = TRUE THEN
        RETURN GREATEST(0, overdue_amount);
ELSE
        RETURN 0;
END IF;
END$$

DELIMITER ;
