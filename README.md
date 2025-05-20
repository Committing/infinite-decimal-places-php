# Infinite Decimal Places - PHP Math Class

A lightweight PHP class for performing mathematical operations with arbitrary precision, supporting infinite decimal places (limited only by system memory and execution time).

This class leverages PHP's native `bc` (Binary Calculator) extension.

## Features

- Arbitrary precision math
- Addition, subtraction, multiplication, division, exponentiation
- Absolute values and comparisons
- Precision control
- Utility functions (rounding, max/min)

## Requirements

- PHP 7.0+
- `bcmath` extension enabled

## Installation

You can include the class directly in your project:

```php
require_once 'math.class.php';
