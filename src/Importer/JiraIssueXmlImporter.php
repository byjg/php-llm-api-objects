<?php

namespace ByJG\LlmApiObjects\Importer;

use ByJG\AnyDataset\Core\AnyDataset;
use ByJG\AnyDataset\Xml\XmlDataset;
use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Model\Message;
use ByJG\XmlUtil\File;

class JiraIssueXmlImporter
{
    public function __construct(
        public string $pathXml,
    )
    {
    }

    /*

    <title>
[BE-877] Analisar como obter Geolocalização do usuário
</title>
<link>https://kingpanda.atlassian.net/browse/BE-877</link>
<project id="10002" key="BE">KingPanda Backend</project>
<description>
<p>Analisar opções, custos, quais fluxos aplicar e as vantagens/desvantagens e agendar reunião com Cassio dia 24/01 para apresentar soluções.</p> <p>Obter Geolocalização do usuário em:</p> <ul> <li>Aposta / Login) &gt;</li> <li>Outros fluxos? <ul> <li>Se usuário fizer login em um dado momento / ip / localização. E um tempo fez uma aposta em outro ip / localização. Ver se faz sentido o deslocamento, se não fizer bloquear acesso e emitir alerta.</li> </ul> </li> </ul>
</description>
<environment/>
<key id="13806">BE-877</key>
<summary>Analisar como obter Geolocalização do usuário</summary>
<type id="10002" iconUrl="https://kingpanda.atlassian.net/rest/api/2/universal_avatar/view/type/issuetype/avatar/10318?size=medium">Task</type>
<priority id="1" iconUrl="https://kingpanda.atlassian.net/images/icons/priorities/highest.svg">Highest</priority>
<status id="10001" iconUrl="https://kingpanda.atlassian.net/" description="">Selected for Development</status>
<statusCategory id="2" key="new" colorName="blue-gray"/>
<resolution id="-1">Unresolved</resolution>
<assignee accountid="63d1dcd3ce7f4b4e14fa2914">João Gilberto Magalhaes</assignee>
<reporter accountid="712020:e48bcfb2-798a-4e2f-bc9d-bd64156d50c9">Robson</reporter>
<labels>
</labels>
<created>Fri, 17 Jan 2025 12:33:52 -0600</created>
<updated>Fri, 17 Jan 2025 12:37:17 -0600</updated>
<due/>
<votes>0</votes>
<watches>1</watches>
<attachments> </attachments>
<subtasks> </subtasks>
<customfields>
</customfields>

     */
    public function parse(): array
    {
        $xmlDataset = new XmlDataset(new File($this->pathXml), 'channel/item', [
            'title' => 'title',
            'link' => './link',
            'project' => 'project',
            'description' => 'description',
            'environment' => 'environment',
            'key' => './key',
            'summary' => 'summary',
            'type' => 'type',
            'priority' => 'priority',
            'status' => 'status',
            'resolution' => 'resolution',
            'assignee' => 'assignee',
            'reporter' => 'reporter',
            'labels' => 'labels/label',
            'created' => 'created',
            'updated' => 'updated',
            'due' => 'due',
        ]);

        return $xmlDataset->getIterator()->toArray();
    }

    public function toMessages(array &$messages): void
    {
        $messages[] = new Message(
            role: Role::user,
            message: "As informacões abaixo são os tickets/issues das atividades no Jira. Preciso que guarde todas as informações para mim, como o status/situacao, prioridade, etc."
        );
        $messages[] = new Message(
            role: Role::assistant,
            message: 'OK. Eu vou guardar essas informações para você.'
        );

        foreach ($this->parse() as $item) {
            $description = strip_tags($item['description']);
            $labels = is_array($item['labels']) ? implode(', ', $item['labels']) : $item['labels'];

            $texto =
                "Nome do ticket: {$item['title']}\n" .
                "Link do ticket: {$item['link']}\n" .
                "Projeto: {$item['project']}\n" .
                "Descrição: {$description}\n" .
//                "Ambiente: {$item['environment']}\n" .
                "Chave: {$item['key']}\n" .
                "Resumo: {$item['summary']}\n" .
                "Tipo: {$item['type']}\n" .
                "Prioridade: {$item['priority']}\n" .
                "Status: {$item['status']}\n" .
                "Resolução: {$item['resolution']}\n" .
                "Responsável: {$item['assignee']}\n" .
                "Relator: {$item['reporter']}\n" .
                "Labels: {$labels}\n" .
                "Criado: {$item['created']}\n" .
                "Atualizado: {$item['updated']}\n" .
//                "Devido: {$item['due']}\n" .
                "\n";

            $messages[] = new Message(
                role: Role::user,
                message: $texto
            );

            $messages[] = new Message(
                role: Role::assistant,
                message: "OK. Guardei as informações do ticket {$item['key']}."
            );
        }
    }

}