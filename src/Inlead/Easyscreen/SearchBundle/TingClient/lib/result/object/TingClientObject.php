<?php
namespace Inlead\Easyscreen\SearchBundle\TingClient\lib\result\object;

class TingClientObject {
  public $id;
  public $data;

  /**
   * Return object type.
   **/
  public function getType() {
    return (string) @$this->record['dc:type']['dkdcplus:BibDK-Type'][0];
  }

  public function getId() {
    return (string) @$this->record['ac:identifier'][''][0];
  }

  public function getTitle() {
    return (string) @$this->record['dc:title'][''][0];
  }

  public function getCreator($type = 'oss:sort') {
    return (string) @$this->record['dc:creator'][$type][0];
  }

  public function getAbstract() {
    return (string) @$this->record['dcterms:abstract'][''][0];
  }

  public function getAudience() {
    return (string) @$this->record['dcterms:audience'][''][0];
  }

  public function getDate() {
    return intval(@$this->record['dc:date'][''][0]);
  }

  public function getPartOf($type = '') {
    return (string) @$this->record['dcterms:isPartOf'][$type][0];
  }

  public function getDescription() {
    return (string) @$this->record['dc:description'][''][0];
  }

  public function getRelationType() {
    return (string) @$this->relationType;
  }

  public function getLinkTitle() {
    return (string) @$this->record['dc:identifier']['dcterms:URI'][0];
  }
}
