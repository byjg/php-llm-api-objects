---
id: model-parameters
title: Model Parameters
sidebar_label: Model Parameters
sidebar_position: 5
---

# Model Parameters

`ModelFileParameters` is an enum of generation parameters that Ollama exposes via its Modelfile. These parameters are serialized under the `options` key in the `toApi()` output (which is what Ollama's `/v1` endpoint expects).

```php
use ByJG\LlmApiObjects\Enum\ModelFileParameters;

$chat->setParameter(ModelFileParameters::Temperature, 0.7);
```

`setParameter()` validates the value before accepting it. Passing an invalid value throws `\InvalidArgumentException`.

:::warning
These parameters are Ollama-specific. They are sent under the `options` key, which is not a standard OpenAI API field. If you use this with the OpenAI API directly, the extra `options` key will be ignored or may cause an error.
:::

## Parameter Reference

| Enum case | API key | Type | Valid values | Default | Description |
|---|---|---|---|---|---|
| `Mirostat` | `mirostat` | `int` | `0`, `1`, `2` | `0` | Enable Mirostat sampling. `0` = disabled, `1` = Mirostat, `2` = Mirostat 2.0 |
| `MirostatEta` | `mirostat_eta` | `float` | `≥ 0` | `0.1` | Mirostat learning rate — how quickly the algorithm responds to feedback |
| `MirostatTau` | `mirostat_tau` | `float` | `≥ 0` | `5.0` | Controls the balance between coherence and diversity |
| `NumCtx` | `num_ctx` | `int` | `> 0` | `2048` | Context window size in tokens |
| `RepeatLastN` | `repeat_last_n` | `int` | `≥ -1` | `64` | How far back to look to prevent repetition. `0` = disabled, `-1` = `num_ctx` |
| `RepeatPenalty` | `repeat_penalty` | `float` | `> 0` | `1.1` | Strength of repetition penalty. Higher = penalizes more |
| `Temperature` | `temperature` | `float` | `> 0` | `0.8` | Creativity of responses. Higher = more creative |
| `Seed` | `seed` | `int` | `≥ 0` | `0` | Random seed for reproducible generation |
| `Stop` | `stop` | `string` | any string | — | Stop sequence. Generation halts when this pattern is encountered |
| `TfsZ` | `tfs_z` | `float` | `≥ 1` | `1` | Tail free sampling. Higher = reduces impact of low-probability tokens. `1.0` = disabled |
| `NumPredict` | `num_predict` | `int` | `≥ -1` | `-1` | Max tokens to generate. `-1` = infinite |
| `TopK` | `top_k` | `int` | `> 0` | `40` | Limits token selection to the top K tokens. Higher = more diverse |
| `TopP` | `top_p` | `float` | `> 0`, `≤ 1` | `0.9` | Nucleus sampling threshold. Works with `top_k` |
| `MinP` | `min_p` | `float` | `> 0`, `≤ 1` | — | Minimum token probability relative to the most likely token |

## Example

```php
use ByJG\LlmApiObjects\Enum\ModelFileParameters;
use ByJG\LlmApiObjects\Model\Chat;
use ByJG\LlmApiObjects\Model\Message;
use ByJG\LlmApiObjects\Enum\Role;

$chat = new Chat(
    model: 'llama3',
    messages: [
        new Message(role: Role::User, message: 'Write a short poem about the sea.'),
    ],
);

$chat->setParameter(ModelFileParameters::Temperature, 0.9);
$chat->setParameter(ModelFileParameters::NumCtx, 4096);
$chat->setParameter(ModelFileParameters::Seed, 42);
$chat->setParameter(ModelFileParameters::NumPredict, 200);

print_r($chat->toApi());
// 'options' => ['temperature' => 0.9, 'num_ctx' => 4096, 'seed' => 42, 'num_predict' => 200]
```

## Validation

Each parameter has strict validation. Invalid values throw `\InvalidArgumentException`:

```php
// OK
$chat->setParameter(ModelFileParameters::Mirostat, 1);

// Throws InvalidArgumentException — mirostat only accepts 0, 1, or 2
$chat->setParameter(ModelFileParameters::Mirostat, 3);

// Throws InvalidArgumentException — temperature must be > 0
$chat->setParameter(ModelFileParameters::Temperature, -0.5);
```
