<?php

namespace ByJG\LlmApiObjects\Importer;

use ByJG\AnyDataset\Xml\XmlDataset;
use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Model\Message;
use ByJG\XmlUtil\File;

class JiraRSSCommentsImporter
{
    public function __construct(
        protected string $pathRss
    )
    {
    }

    public function parse(): array
    {
        $dataset = new XmlDataset(
            new File($this->pathRss),
            'channel/item',
            [
                'title' => 'title',
                'link' => './link',
                'pubDate' => 'pubDate',
                'author' => 'author',
                'description' => 'description',
                'guid' => 'guid'
            ]);
        return $dataset->getIterator()->toArray();
    }

    public function toMessages(array &$messages): void
    {
        $messages[] = new Message(
            role: Role::User,
            message: 'As informacoes abaixo são os comentários dos atividades no Jira'
        );
        $messages[] = new Message(
            role: Role::Assistant,
            message: 'OK. Eu vou guardar essas informações para você.'
        );

        foreach ($this->parse() as $item) {
            $description = strip_tags($item['description']);
            $texto =
                "Nome do ticket: {$item['title']}\n" .
                "comentário: {$description}}\n" .
                "autor do comentário: {$item['author']}\n" .
                "data do comentário: {$item['pubdate']}\n" .
                "\n";

            $messages[] = new Message(
                role: Role::User,
                message: $texto
            );
            $messages[] = new Message(
                role: Role::Assistant,
                message: 'OK.'
            );
        }
    }
}
