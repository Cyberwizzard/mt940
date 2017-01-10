<?php

/*
 * This file is part of the Cyberwizzard\MT940 library which is a fork of the Jejik\MT940 library
 *
 * Copyright (c) 2017 Berend Dekens <cyberwizzard@gmail.com>
 * Copyright (c) 2012 Sander Marechal <s.marechal@jejik.com>
 * Licensed under the MIT license
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

namespace cyberwizzard\MT940\Parser;

use Jejik\MT940\Parser\AbstractParser;

use cyberwizzard\MT940\Parser\ParserLogger;

class Rabobank extends AbstractParser
{
    var $logger = null;

    public function __construct(\Jejik\MT940\Reader $reader) {
        parent::__construct($reader);
        if(!$this->logger)
            $this->logger = new ParserLogger();

        // Uncomment the next line to enable debugging
        //$this->logger->setLogLevel(LOG_DEBUG)->setDirectOutput(true);
        // Uncomment the next line to make the parser silent
        $this->logger->setLogLevel(LOG_ERR)->setDirectOutput(false)->setBufferOutput(false);

        // Print a start banner
        $this->logger->log(LOG_INFO, "Instantiated Rabobank parser class");
    }

    /**
     * Test if the document is a Rabobank document
     *
     * @param string $text
     * @return bool
     */
    public function accept($text)
    {
        $match = substr($text, 0, 5) === ':940:';
        if($match)
            $this->logger->log(LOG_NOTICE, "File matches Rabobank MT940 format");
        return $match;
    }

    /**
     * Parse an account number - format is ":25:IBANNUMBER CURRENCY"
     *
     * @param string $text Statement body text
     * @return string|null
     */
    protected function accountNumber($text)
    {
        if ($account = $this->getLine('25', $text)) {
            $this->logger->log(LOG_DEBUG, "Found account number line");
            if (preg_match('/^([A-Z0-9.]+)(\s+[A-Z]+)?/', $account, $match)) {
                $accountno = str_replace('.', '', $match[1]);
                $this->logger->log(LOG_INFO, "Found account number: " . $accountno);
                return $accountno;
            } else {
                $this->logger->log(LOG_NOTICE, "Could not extract account number");
                $this->logger->log(LOG_DEBUG, "Regex fail for account number in this line: " . $account);    
            }
        } else {
            $this->logger->log(LOG_NOTICE, "Line does not contain account number");
            $this->logger->log(LOG_DEBUG, "Expected account number with code 25 in these lines: " . $text);
        }

        return null;
    }

    /**
     * Rabobank does not use statement numbers. Use the opening balance
     * date as statement number instead.
     *
     * @param string $text Statement body text
     * @return string|null
     */
    protected function statementNumber($text)
    {
        if ($line = $this->getLine('60F', $text)) {
            if (preg_match('/(C|D)(\d{6})([A-Z]{3})([0-9,]{1,15})/', $line, $match)) {
                $this->logger->log(LOG_INFO, "Obtained opening balance as statement number: " . $match[2]);    
                return $match[2];
            } else {
                $this->logger->log(LOG_WARNING, "Could not extract opening balance");
                $this->logger->log(LOG_DEBUG, "Regex fail for opening balance in this line: " . $line);
            }
        } else {
            $this->logger->log(LOG_WARNING, "Line does not contain an opening balance");
            $this->logger->log(LOG_DEBUG, "Expected opening balance with code 60F in these lines: " . $text);
        }

        return null;
    }

    /**
     * Get the contra account from a transaction
     * Format for :61:
     * Field 1: 6 digits, valuation date (group 1)
     * Field 2: 4 digits, book date (group 2, optional)
     * Field 3: 1 to 2 characters, C|CR|D for credit or debet (group 3)
     * Field 4: 1 character, capital code (not supported as of now)
     * Field 5: 15 digits including a comma, amount (group 4)
     * Field 6: character plus 3 digits or 4 characters 'NMSC', transaction type (group 5)
     * Field 7: sometimes there is garbage in here like NONREF, EREF or MARF (group 6, optional)
     * Field 7: 8 to 24 characters, contra account number (group 7), note: version 2.4 of the MT940 Rabobank spec
     *          does not mention the garbage strings and only lists 16 characters, but that is not enough for most IBAN numbers,
     *          and too short for non-IBAN numbers, so now its 8 to 24
     *
     * @param array $lines The transaction text at offset 0 and the description at offset 1
     * @return string|null
     */
    protected function contraAccountNumber(array $lines)
    {
        $this->logger->log(LOG_DEBUG, "Called contraAccountNumber with: " . print_r($lines, true));
        if (!preg_match('/(\d{6})(\d{4})?((?:C|D)R?)([0-9,]{15})(N\d{3}|NMSC)([A-Z]+\s+)?([0-9A-Z]{8,24})/', $lines[0], $match)) {
            $this->logger->log(LOG_WARNING, "Regex fail for contra account number this line: " . $lines[0]);
            return null;
        }

        $contraAccount = rtrim(ltrim($match[7]));
        $this->logger->log(LOG_INFO, "Found contra account number " . $contraAccount);

        return $contraAccount;
    }

    /**
     * Get the contra account holder name from a transaction
     *
     * @param array $lines The transaction text at offset 0 and the description at offset 1
     * @return string|null
     */
    protected function contraAccountName(array $lines)
    {
        //if (!preg_match('/(\d{6})((?:C|D)R?)([0-9,]{15})(N\d{3}|NMSC)([0-9P ]{16}|NONREF)(.*)/', $lines[0], $match)) {
        //    return null;
        //}

        //$name = trim($match[6]);

        $this->logger->log(LOG_DEBUG, "Called contraAccountName with: " . print_r($lines, true));

        if(strpos($lines[1], 'ORDP') === false) {
            $this->logger->log(LOG_INFO, "This line type specifies no contra account name");
            return null;
        }

        if (!preg_match('/\/NAME\/(.+)(\/(ADDR|REMI)\/)/sU', $lines[1], $match)) {
            // Only the ORDP type has a name field
            $this->logger->log(LOG_WARNING, "Regex fail for contra account name this line: " . $lines[1]);
            return null;
        }

        $name = rtrim(ltrim($match[1]));
        $this->logger->log(LOG_INFO, "Found contra account name " . $name);

        return $name ?: null;
    }
}
