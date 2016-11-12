<?php

/*
 * This file is meant as an extension of the Jejik\MT940 library to provide CSV parsing for Rabobank CSV exports
 *
 * Copyright (c) 2015 Berend Dekens <cyberwizzard@gmail.com>
 * Licensed under the GNU GPLv2 license
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

namespace cyberwizzard\MT940\Parser;
use Jejik\MT940\Parser\AbstractParser;

class RabobankCSV extends AbstractParser
{
    // Fields as specified by the 2013 CSV format from the Rabobank
    const F_REKENINGNUMMER_REKENINGHOUDER = 0;
    const F_MUNTSOORT = 1;
    const F_RENTEDATUM = 2;
    const F_BY_AF_CODE = 3;
    const F_BEDRAG = 4;
    const F_TEGENREKENING = 5;
    const F_NAAR_NAAM = 6;
    const F_BOEKDATUM = 7;
    const F_BOEKCODE = 8;

    const F_OMSCHRIJVING1 = 10;
    const F_OMSCHRIJVING2 = 11;
    const F_OMSCHRIJVING3 = 12;
    const F_OMSCHRIJVING4 = 13;
    const F_OMSCHRIJVING5 = 14;
    const F_OMSCHRIJVING6 = 15;

    /**
     * Test if the document is a Rabobank CSV formatted file
     *
     * @param string $text
     * @return bool
     */
    public function accept($text)
    {
        $line = strtok($text, "\n");
        $fields = str_getcsv($line);

	// TODO: When more CSV formats are to be supported, more format checks could be added
	// Rabobank CSV uses 19 fields and the 4th field denotes Credit or Debet
        return (count($fields) == 19 && ($fields[self::F_BY_AF_CODE] == 'C' || $fields[self::F_BY_AF_CODE] == 'D'));       }

    /**
     * Parse a CSV formatted text file from the Rabobank
     */
    public function parse($text) {
        $statements = array();
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            if(trim($line) == '') continue;
            if ($statement = $this->parseLine($line)) {
                $statements[] = $statement;
            }
        }

        return $statements;
    }

    /**
     * Parse a line of CSV
     */
    protected function parseLine($line) {
        static $statement_number = 1;
        $fields = str_getcsv($line);
        if(count($fields) != 19)
            throw new \RuntimeException('Not an Rabobank CSV statement: ' . $line);

        $accountNumber = $fields[self::F_REKENINGNUMMER_REKENINGHOUDER];
        $account = $this->reader->createAccount(null);

        if (!($account instanceof Jejik\MT940\AccountInterface)) {
		return null;
        }

        $account->setNumber($fields[self::F_REKENINGNUMMER_REKENINGHOUDER]);
        $statement = $this->reader->createStatement($account, $statement_number);

        if (!($statement instanceof Jejik\MT940\StatementInterface)) {
		return null;
        }

        $statement->setAccount($account)
                  ->setNumber($statement_number);

        // Parse the amount
        $amount = (float) $fields[self::F_BEDRAG];
        if ($fields[self::F_BY_AF_CODE] == 'D') {
            $amount *= -1;
        }

        // Parse dates
        $valueDate = \DateTime::createFromFormat('Ymd', $fields[self::F_RENTEDATUM]);
        $valueDate->setTime(0,0,0);

        $bookDate = \DateTime::createFromFormat('Ymd', $fields[self::F_BOEKDATUM]);
        $bookDate->setTime(0,0,0);

        // Contract account
        $caccount = $this->reader->createAccount(null);
        $caccount->setNumber($fields[self::F_TEGENREKENING]);
        $caccount->setName($fields[self::F_NAAR_NAAM]);

        $description = $fields[self::F_OMSCHRIJVING1] . $fields[self::F_OMSCHRIJVING2] . $fields[self::F_OMSCHRIJVING3] . $fields[self::F_OMSCHRIJVING4] .
                $fields[self::F_OMSCHRIJVING5] . $fields[self::F_OMSCHRIJVING6];
        $transaction = $this->reader->createTransaction();
        $transaction->setAmount($amount)
                    ->setContraAccount($caccount)
                    ->setValueDate($valueDate)
                    ->setBookDate($bookDate)
                    ->setDescription($this->description($description));

        $statement->addTransaction($transaction);

        // Increase the statement number
        $statement_number = $statement_number + 1;

        return $statement;
    }
}
