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

$chat->setParameter(ModelFileParameters::temperature, 0.7);
```

`setParameter()` validates the value before accepting it. Passing an invalid value throws `\InvalidArgumentException`.

:::warning
These parameters are Ollama-specific. They are sent under the `options` key, which is not a standard OpenAI API field. If you use this with the OpenAI API directly, the extra `options` key will be ignored or may cause an error.
:::

## Parameter Reference

| Enum case | API key | Type | Valid values | Default | Description |
|---|---|---|---|---|---|
| `mirostat` | `mirostat` | `int` | `0`, `1`, `2` | `0` | Enable Mirostat sampling. `0` = disabled, `1` = Mirostat, `2` = Mirostat 2.0 |
| `mirostatEta` | `mirostat_eta` | `float` | `≥ 0` | `0.1` | Mirostat learning rate — how quickly the algorithm responds to feedback |
| `mirostatTau` | `mirostat_tau` | `float` | `≥ 0` | `5.0` | Controls the balance between coherence and diversity |
| `numCtx` | `num_ctx` | `int` | `> 0` | `2048` | Context window size in tokens |
| `repeatLastN` | `repeat_last_n` | `int` | `≥ -1` | `64` | How far back to look to prevent repetition. `0` = disabled, `-1` = `num_ctx` |
| `repeatPenalty` | `repeat_penalty` | `float` | `> 0` | `1.1` | Strength of repetition penalty. Higher = penalizes more |
| `temperature` | `temperature` | `float` | `> 0` | `0.8` | Creativity of responses. Higher = more creative |
| `seed` | `seed` | `int` | `≥ 0` | `0` | Random seed for reproducible generation |
| `stop` | `stop` | `string` | any string | — | Stop sequence. Generation halts when this pattern is encountered |
| `tfsZ` | `tfs_z` | `float` | `≥ 1` | `1` | Tail free sampling. Higher = reduces impact of low-probability tokens. `1.0` = disabled |
| `numPredict` | `num_predict` | `int` | `≥ -1` | `-1` | Max tokens to generate. `-1` = infinite |
| `topK` | `top_k` | `int` | `> 0` | `40` | Limits token selection to the top K tokens. Higher = more diverse |
| `topP` | `top_p` | `float` | `> 0`, `≤ 1` | `0.9` | Nucleus sampling threshold. Works with `top_k` |
| `minP` | `min_p` | `float` | `> 0`, `≤ 1` | — | Minimum token probability relative to the most likely token |

## Example

```php
use ByJG\LlmApiObjects\Enum\ModelFileParameters;
use ByJG\LlmApiObjects\Model\Chat;
use ByJG\LlmApiObjects\Model\Message;
use ByJG\LlmApiObjects\Enum\Role;

$chat = new Chat(
    model: 'llama3',
    messages: [
        new Message(role: Role::user, message: 'Write a short poem about the sea.'),
    ],
);

$chat->setParameter(ModelFileParameters::temperature, 0.9);
$chat->setParameter(ModelFileParameters::numCtx, 4096);
$chat->setParameter(ModelFileParameters::seed, 42);
$chat->setParameter(ModelFileParameters::numPredict, 200);

print_r($chat->toApi());
// 'options' => ['temperature' => 0.9, 'num_ctx' => 4096, 'seed' => 42, 'num_predict' => 200]
```

## Validation

Each parameter has strict validation. Invalid values throw `\InvalidArgumentException`:

```php
// OK
$chat->setParameter(ModelFileParameters::mirostat, 1);

// Throws InvalidArgumentException — mirostat only accepts 0, 1, or 2
$chat->setParameter(ModelFileParameters::mirostat, 3);

// Throws InvalidArgumentException — temperature must be > 0
$chat->setParameter(ModelFileParameters::temperature, -0.5);
```
