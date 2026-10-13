<?php

namespace ByJG\LlmApiObjects\Enum;

enum ModelFileParameters: string
{
    // Enable Mirostat sampling for controlling perplexity.
    // (default: 0, 0 = disabled, 1 = Mirostat, 2 = Mirostat 2.0)
    case Mirostat = 'mirostat';

    // Influences how quickly the algorithm responds to feedback from the generated text.
    // A lower learning rate will result in slower adjustments, while a higher learning rate will make the algorithm more responsive.
    // (Default: 0.1)
    case MirostatEta = 'mirostat_eta';

    // Controls the balance between coherence and diversity of the output.
    // A lower value will result in more focused and coherent text.
    // (Default: 5.0)
    case MirostatTau = 'mirostat_tau';

    // Sets the size of the context window used to generate the next token.
    // (Default: 2048)
    case NumCtx = 'num_ctx';

    // Sets how far back for the model to look back to prevent repetition.
    // (Default: 64, 0 = disabled, -1 = num_ctx)
    case RepeatLastN = 'repeat_last_n';

    // Sets how strongly to penalize repetitions.
    // A higher value (e.g., 1.5) will penalize repetitions more strongly, while a lower value (e.g., 0.9) will be more lenient.
    // (Default: 1.1)
    case RepeatPenalty = 'repeat_penalty';

    // The temperature of the model.
    // Increasing the temperature will make the model answer more creatively.
    // (Default: 0.8)
    case Temperature = 'temperature';

    // Sets the random number seed to use for generation.
    // Setting this to a specific number will make the model generate the same text for the same prompt.
    // (Default: 0)
    case Seed = 'seed';

    // Sets the stop sequences to use.
    // When this pattern is encountered the LLM will stop generating text and return.
    // Multiple stop patterns may be set by specifying multiple separate `stop` parameters in a modelfile.
    case Stop = 'stop';

    // Tail free sampling is used to reduce the impact of less probable tokens from the output.
    // A higher value (e.g., 2.0) will reduce the impact more, while a value of 1.0 disables this setting.
    // (default: 1)
    case TfsZ = 'tfs_z';

    // Maximum number of tokens to predict when generating text.
    // (Default: -1, infinite generation)
    case NumPredict = 'num_predict';

    // Reduces the probability of generating nonsense.
    // A higher value (e.g. 100) will give more diverse answers, while a lower value (e.g. 10) will be more conservative.
    // (Default: 40)
    case TopK = 'top_k';

    // Works together with top-k.
    // A higher value (e.g., 0.95) will lead to more diverse text, while a lower value (e.g., 0.5) will generate more focused and conservative text.
    // (Default: 0.9)
    case TopP = 'top_p';

    // Alternative to the top_p, and aims to ensure a balance of quality and variety.
    // The parameter *p* represents the minimum probability for a token to be considered, relative to the probability of the most likely token.
    // For example, with *p*=0.05 and the most likely token having a probability of 0.9, logits with a value less than 0.045
    case MinP = 'min_p';

    public function isValid(mixed $value): bool
    {
        return match ($this) {
            self::Mirostat => is_int($value) && in_array($value, [0, 1, 2]),
            self::MirostatEta, self::MirostatTau => is_float($value) && $value >= 0,
            self::NumCtx, self::TopK => is_int($value) && $value > 0,
            self::RepeatLastN => is_int($value) && ($value >= -1),
            self::RepeatPenalty, self::Temperature => is_float($value) && $value > 0,
            self::Seed => is_int($value) && $value >= 0,
            self::Stop => is_string($value),
            self::TfsZ => is_float($value) && $value >= 1,
            self::NumPredict => is_int($value) && $value >= -1,
            self::TopP, self::MinP => is_float($value) && $value > 0 && $value <= 1,
        };
    }
}