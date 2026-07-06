<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Thrown when an AI tool's backing capability hasn't shipped yet.
 * The orchestrator turns this into an honest "I can't do that yet"
 * instead of letting the model improvise (guardrail, Blueprint §13).
 */
class AiToolUnavailableException extends RuntimeException
{
}
