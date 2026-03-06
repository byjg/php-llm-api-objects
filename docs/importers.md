---
id: importers
title: Importers
sidebar_label: Importers
sidebar_position: 6
---

# Importers

Importers parse external data sources and convert them into `Message` arrays that can be fed directly into a `Chat` model. This is useful for loading context (e.g. project tickets, comments) into the conversation before asking questions.

## JiraIssueXmlImporter

Parses a Jira issue XML export and converts each issue into a pair of `user`/`assistant` messages that establish the issues as known context.

```php
use ByJG\LlmApiObjects\Importer\JiraIssueXmlImporter;
```

### Input Format

The XML file must be a Jira issue export (RSS/XML format, e.g. exported from `Issues > Export XML` in Jira). Each `<item>` element under `<channel>` is parsed for the following fields:

`title`, `link`, `project`, `description`, `environment`, `key`, `summary`, `type`, `priority`, `status`, `resolution`, `assignee`, `reporter`, `labels`, `created`, `updated`, `due`

### Usage

```php
$messages = [];

$importer = new JiraIssueXmlImporter('/path/to/jira-export.xml');
$importer->toMessages($messages);

// $messages now contains user/assistant pairs for each issue
```

### Methods

| Method | Returns | Description |
|---|---|---|
| `parse()` | `array` | Parses the XML and returns all issues as an array of associative arrays |
| `toMessages(array &$messages)` | `void` | Appends the issue context messages to `$messages` |

### Message Structure

`toMessages()` appends:
1. A `user` message asking the model to store the Jira information
2. An `assistant` acknowledgement
3. For each issue: a `user` message with all issue fields, followed by an `assistant` acknowledgement

### Full Example

```php
<?php
use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Importer\JiraIssueXmlImporter;
use ByJG\LlmApiObjects\Importer\JiraRSSCommentsImporter;
use ByJG\LlmApiObjects\Model\Chat;
use ByJG\LlmApiObjects\Model\Message;

$messages = [];

// Load issues
$issues = new JiraIssueXmlImporter('/path/to/jira-issues.xml');
$issues->toMessages($messages);

// Load comments
$comments = new JiraRSSCommentsImporter('/path/to/jira-comments.xml');
$comments->toMessages($messages);

// Ask a question about the loaded data
$messages[] = new Message(
    role: Role::user,
    message: 'What is the average time between ticket creation and last update?',
);

$chat = new Chat(
    model: 'gpt-4o',
    messages: $messages,
    system: 'Answer questions based on the Jira tickets provided in the conversation.',
);

$result = $client->chat()->create($chat->toApi());
echo $result->choices[0]->message->content;
```

---

## JiraRSSCommentsImporter

Parses a Jira RSS feed of issue comments and converts each comment into conversation context messages.

```php
use ByJG\LlmApiObjects\Importer\JiraRSSCommentsImporter;
```

### Input Format

The RSS file must be a Jira activity/comment RSS export. Each `<item>` element is parsed for:

`title`, `link`, `pubDate`, `author`, `description`, `guid`

### Usage

```php
$messages = [];

$importer = new JiraRSSCommentsImporter('/path/to/jira-comments.xml');
$importer->toMessages($messages);
```

### Methods

| Method | Returns | Description |
|---|---|---|
| `parse()` | `array` | Parses the RSS and returns all comment items as an array |
| `toMessages(array &$messages)` | `void` | Appends the comment context messages to `$messages` |

### Message Structure

`toMessages()` appends:
1. A `user` message stating the comments are from Jira
2. An `assistant` acknowledgement
3. For each comment: a `user` message with ticket name, comment text, author, and date, followed by an `assistant` acknowledgement

:::info
Both importers generate messages in Portuguese (`pt-BR`) for the context-setting prompts, as they were designed for a Portuguese-speaking workflow. The ticket content itself is passed through as-is from the Jira export.
:::

:::tip
Use `parse()` instead of `toMessages()` if you want to process the raw data yourself before building messages.
:::
