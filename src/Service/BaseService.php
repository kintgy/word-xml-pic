<?php

namespace App\Service;

class BaseService
{
    private $docXml;
    private $docXmlDir = 'word/document.xml';

    public function getContent($filename)
    {
        $zip = new \ZipArchive();
        $zip->open($filename);
        $docIndex = $zip->locateName($this->docXmlDir);
        $docXml = $zip->getFromIndex($docIndex);

        $zip->close();

        $this->docXml = new \DOMDocument();
        $this->docXml->encoding = mb_detect_encoding($docXml);
        $this->docXml->preserveWhiteSpace = false;
        $this->docXml->formatOutput = true;
        $this->docXml->textContent = trim($this->docXml->textContent);
        $this->docXml->loadXML($docXml);

        $pics = $this->docXml->getElementsByTagName('drawing');

        foreach ($pics as $key => $pic) {
            $node = $pics->item($key);

            $picIndex = $node->getElementsByTagName('blip')->item(0)->getAttribute('r:embed');
            $img = $this->dealPic(substr($picIndex, 3), $filename);

            $pics->item($key)->nodeValue = $img;
        }

        $paragraphs = $this->docXml->getElementsByTagName('p');
        foreach ($paragraphs as $key => $paragraph) {
            echo $paragraphs->item($key)->nodeValue . '<br>';
        }

        var_dump($this->docXml->textContent);
    }

    private function dealPic($index, $fileName)
    {
        $imgName = 'word/media/image' . ($index - 1);
        $zip = zip_open($fileName);

        while ($zipEntry = zip_read($zip)) {
            $entryName = zip_entry_name($zipEntry);
            if (strstr($entryName, $imgName) != false) {
                $ext = pathinfo($entryName, PATHINFO_EXTENSION);
                $content = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
                zip_close($zip);
                return sprintf('<img src="data:image/%s;base64,%s">', $ext, base64_encode($content));
            }
        }
        zip_close($zip);
    }
}