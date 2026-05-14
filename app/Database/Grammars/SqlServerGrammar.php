<?php

namespace App\Database\Grammars;

use Illuminate\Database\Query\Grammars\SqlServerGrammar as BaseGrammar;

class SqlServerGrammar extends BaseGrammar
{
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }
}
