<?php

namespace App\Exceptions;

/** A transport/auth failure talking to MTBC's Empower Payment API (auth, tokenize, detokenize). A
 *  card decline from Create_Charge is a normal business outcome, not this exception — see ChargeResult. */
class EmpowerPaymentApiException extends \RuntimeException {}
