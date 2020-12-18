class WebScraper {
    public $obj, $ishtml = null, $query, $dom, $xpath;

    public function __construct() {
        $this->dom = new DOMDocument();
    }

    public function loadHTMLFile($url){
        libxml_use_internal_errors(true);
        $this->dom->loadHTMLFile($url, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors(false);
        $this->xpath = new DOMXPath($this->dom);
        $this->ishtml = true;
    }

    public function loadXML($XML){
        $this->dom->loadXML($XML);
        $this->xpath = new DOMXPath($this->dom);
        $this->ishtml = false;
    }
    
    public function loadHTML($HTML){
        $this->dom->loadHTML($HTML);
        $this->xpath = new DOMXPath($this->dom);
        $this->ishtml = true;
    }

    private function convert2XPath($query){
        $xpath = $query;
        
        $xpath = preg_replace(
        	[
                "/::/",
            	"/([^,])\s/", 
                "/\/>\//",
                
                "/\[([^\.\s\/\#\[\,]+)='(([^\s\.]+)\.([^\s\.]+))+'\]/",
                
                "/:first-child/",
                "/:last-child/",
               
                "/([^\.\s\/\#\[,]+),\s([^\.\s\/\#\[,]+)/",
               	"/,\s([^\.\s\/\#\[,]+)/",
                "/((self::[^\.\s\/\#\[,]+ or\s)+(self::[^\.\s\/\#\[,]+)+)/",
                "/self::self::/",
                "/\/{1,2}\[/",
                "/^\[/",
                
                "/\.([^\.\s\/\#\[,]+)/",
                "/\/{1,2}\[/",
                "/^\[/",
                
                "/\#([^\.\s\/\#\[,]+)/",
                "/\/{1,2}\[/",
                "/^\[/",
                
                "/\[([^\.\s\/\#\[\,]+)='([^\s]+)'\]/",
                "/\/{1,2}\[/",
                "/^\[/",
                
                "/{dot}/",
                "/::text/",
                "/::comment/",
                "/::attributes/"
            ],
            [
                " ::",
                "$1/",
                "/",
                
                "[$1='$3{dot}$4']",
                
                "[1]",
                "[last()]",
                
                "self::$1 or self::$2",
                " or self::$1",
                "[$1]",
                "self::",
                "/*[",
                "*[",
                
                "[contains(@class, '$1')]",
                "/*[",
                "*[",
                
                "[contains(@id, '$1')]",
                "/*[",
                "*[",
                
                "[contains(@$1, '$2')]",
                "/*[",
                "*[",
                
                ".",
                "text()",
                "comment()",
                "@*"
            ],
            $xpath
        );
        
        return $xpath;
    }
    
    public function Q($query){
        $this->query = $query; 
        $query = $this->convert2XPath($query);
        
        $this->obj = $this->xpath->query("//$query");
        
        return $this;
    }
    
    private function _($query){
        $query = $this->convert2XPath($query);
        
        $this->obj = $this->xpath->query("//$query");
        
        return $this;
    }
    
    public function query($query){
        $this->query = $query; 
        $query = $this->convert2XPath($query);
        
        $this->obj = $this->xpath->query("//$query");
        
        return $this;
    }
    
    public function setAttribute($attr, $value){
        foreach ($this->obj as $item){
            $item->setAttribute("$attr", "$value");
        }
        $this->obj = null;
    }

    public function removeAttribute($attr){
        foreach ($this->obj as $item){
            $item->removeAttribute("$attr");
        }
        $this->obj = null;
    }

    public function addClass($class){
        foreach ($this->obj as $item){
            $otherClasses = $item->getAttribute("class");
            $newClasses = trim("$otherClasses $class");
            $item->setAttribute("class", "$newClasses");
        }
        $this->obj = null;
    }

    public function removeClass($class){
        foreach ($this->obj as $item){
            $newClasses = trim(str_replace($class, "", $item->getAttribute("class")));
            $item->setAttribute("class", "$newClasses");
        }
        $this->obj = null;
    }

    public function html($html = null){
        
        if (!isset($html)){
            $html = '';
            
            foreach($this->obj as $item){
                $children = $item->childNodes;
                foreach ($children as $child) {
                    $html .= $child->ownerDocument->saveXML($child);
                }
            }
        
            return $html;
        } else {
            
            $dom = new DOMDocument();
            $dom->loadXML($html);
            $xpath = new DOMXPath($dom);
            
            foreach($this->obj as $item){
                foreach($xpath->query("/*") as $contentNode){
                    $newitem = $this->dom->createElement($item->nodeName);
                    $item->parentNode->replaceChild($newitem, $item);
                    $contentNode = $this->dom->importNode($contentNode, true);
                    $newitem->appendChild($contentNode);
                }
            }
        }
        
        $this->obj = null;
    }

    public function appendHtml($html){
        
        $dom = new DOMDocument();
        $dom->loadXML($html);
        $xpath = new DOMXPath($dom);
            
        foreach($this->obj as $item){
            foreach($xpath->query("//*") as $contentNode){
                $contentNode = $this->dom->importNode($contentNode, true);
                $item->appendChild($contentNode);
            }
        }
        
        $this->obj = null;
    }
    
    public function prependHtml($html){
        
        $dom = new DOMDocument();
        $dom->loadXML($html);
        $xpath = new DOMXPath($dom);
            
        foreach($this->obj as $item){
            foreach($xpath->query("//*") as $contentNode){
                $contentNode = $this->dom->importNode($contentNode, true);
                $item->insertBefore($contentNode, $item->firstChild);
            }
        }
        
        $this->obj = null;
    }
    
    public function delete($keepinner = false){
        
        foreach($this->obj as $item){
            if (!$keepinner){
                $item->parentNode->removeChild($item);
            } else {
                while ($item->firstChild instanceof DOMNode) {
                    $item->parentNode->insertBefore($item->firstChild, $item);
                }
                $item->parentNode->removeChild($item);
            }
        }
        
        $this->obj = null;
    }
    
    public function unwrap(){
        foreach($this->obj as $item){
            while ($item->firstChild instanceof DOMNode) {
                $item->parentNode->insertBefore($item->firstChild, $item);
            }
            $item->parentNode->removeChild($item);
        }
        $this->obj = null;
    }
    
    private function breakUp($tag, &$html, &$keys, &$vals, &$attrs){
        
		$html = preg_replace_callback(
        	'/([^=<>\s]*)=[\'|"]([^=]*)[\'|"]/', 
            function($m){
            	return "{$m[1]} => {$m[2]}]";
            },
            $html
        );
        $html = preg_replace(
        	["/$tag/", "/[^=]>/", "/</", "/\//"], 
            "", 
            $html
        );
        
        $lines = explode("]", $html);
        
        foreach($lines as $index => $value){
            if ($value == "") unset($lines[$index]);
        }
        foreach($lines as $index => $value){
        	$arr = explode('=>', str_replace("]", "", $value));
            array_push($keys, $arr[0]);
            array_push($vals, $arr[1]);
        }
        $attrs = array_combine($keys, $vals);
    }
    
    public function wrap($html){
        $attrs = array();
        $keys = array();
        $vals = array();
            
        if (preg_match("/<([\w\d]+).*>[^<>]*<\/\\1>/", $html)){
            $tag = preg_replace("/<([\w\d]+).*>[^<>]*<\/\\1>/", "$1", $html);
            $this->breakUp($tag, $html, $keys, $vals, $attrs);
        }
        
        foreach($this->obj as $item){
            $wrapper = $this->dom->createElement("$tag");
            foreach($attrs as $attr=>$value){
                $wrapper->setAttribute(trim($attr), trim($value));
            }
            $itemclone = $item->cloneNode(true);
            $wrapper->appendChild($itemclone);
            $this->dom->appendChild($wrapper);
            $item->parentNode->replaceChild($wrapper, $item);
        }
        $this->obj = null;
    }
    
    public function removeEmptyTags(){
        $query = '//*[not(*) and not(@*) and not(text()[normalize-space()])]';
        foreach($this->xpath->query("$query") as $tag){
            $tag->parentNode->removeChild($tag);
        }
        $this->obj = null;
    }
    
    public function empty(){
        $dom = new DOMDocument();
        $dom->loadXML($html = "<empty/>");
        $xpath = new DOMXPath($dom);
            
        foreach($this->obj as $item){
            foreach($xpath->query("/*") as $contentNode){
                $newitem = $this->dom->createElement($item->nodeName);
                $item->parentNode->replaceChild($newitem, $item);
                $contentNode = $this->dom->importNode($contentNode, true);
                $newitem->appendChild($contentNode);
            }
        }
        $this->obj = null;
    }
    
    public function text($text = null){
        foreach($this->obj as $item){
            if (!isset($text)){ 
                return $item->textContent;
            } else {
                $item->textContent = $text;
            }
        }
        $this->obj = null;
    }
    
    public function replaceWith($html){
        
        $dom = new DOMDocument();
        $dom->loadXML($html);
        $xpath = new DOMXPath($dom);
        
        foreach($this->obj as $item){
            foreach($xpath->query("/*") as $replace){
                $replace = $this->dom->importNode($replace, true);
                $item->parentNode->replaceChild($replace, $item);
            }
        }
        $this->obj = null;
    }
    
    public function count(){
        $count = 0;
        foreach($this->obj as $item){
            $count++;
        }
        return $count;
        $this->obj = null;
    }
    
    public function echo($format = true){
        $this->dom->formatOutput = $format;
        printf (($this->ishtml) ? (
            $this->dom->saveHTML()
        ) : (
            $this->dom->saveXML()
        ));
    }
    
    public function replaceText($pattern, $replace, $html = true){
        foreach($this->obj as $item){
            $newtext = preg_replace(
                $pattern,
                $replace,
                $item->textContent
            );
            $item->textContent = $newtext;
        }
        
        if ($html) {
            if ($this->ishtml) { 
                $this->dom->loadHTML(
                    html_entity_decode($this->dom->saveHTML())
                );
            } else {
                $this->dom->loadXML(
                    html_entity_decode($this->dom->saveXML())
                );
            }
            $this->xpath = new DOMXPath($this->dom);
        }
    }
    
    public function replaceTextCallback($pattern, $func, $html = true){
        foreach($this->obj as $item){
            $newtext = preg_replace_callback(
                $pattern,
                function($m) use ($func){
                    return $func($m);
                },
                $item->textContent
            );
            $item->textContent = $newtext;
        }
        
        if ($html) {
            if ($this->ishtml) { 
                $this->dom->loadHTML(
                    html_entity_decode($this->dom->saveHTML())
                );
            } else {
                $this->dom->loadXML(
                    html_entity_decode($this->dom->saveXML())
                );
            }
            $this->xpath = new DOMXPath($this->dom);
        }
    }
    
    public function hasClass($class){
        foreach($this->obj as $item){
            $classes = $item->getAttribute("class");
        }
        $bool = (preg_match("/".preg_quote($class)."/", $classes)) ? true : false;
        return $bool;
    }

    public function hasAttr($attr, $val){
        foreach($this->obj as $item){
            $attrs = $item->getAttribute("$attr");
        }
        $bool = (preg_match("/".preg_quote($val)."/", $attrs)) ? true : false;
        return $bool;
    }
    
    public function iterate($func){
        $i = 1;
        foreach($this->obj as $item){
            $func($this->_($this->query."[$i]"));
            $i++;
        }
    }
}