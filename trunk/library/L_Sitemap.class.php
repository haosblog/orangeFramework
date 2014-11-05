<?php
/**
 *  File: L_Sitemap.class.php
 *  Functionality: 生成网站地图
 *  Author: Nic XIE
 *  Date: 2013-3-6
 *  Remark:
 */

class L_Sitemap{
	
	private $header = "<\x3Fxml version=\"1.0\" encoding=\"UTF-8\"\x3F>\n\t<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
    private $charset = "UTF-8";
    private $footer = "\t</urlset>\n";
    private $items = array();
	
	function __construct(){
		
	}
	
	public function addItem($newItem){
		$this->items[] =  $newItem;
	}
	
	// Create sitemap
	// 返回创建好的 xml string
	public function create(){
		$map = $this->header . "\n";

        foreach ($this->items AS $item){
            $item['loc'] = htmlentities($item['loc'], ENT_QUOTES);
            $map .= "\t\t<url>\n\t\t\t<loc>".$item['loc']."</loc>\n";
			
			// priority
            if ($item['priority']){
                $map .= "\t\t\t<priority>".$item['priority']."</priority>\n";
			}

            // lastmod
            if ($item['lastmod']){
                $map .= "\t\t\t<lastmod>".$item['lastmod']."</lastmod>\n";
			}

            // changefreq
            if ($item['changefreq']){
                $map .= "\t\t\t<changefreq>".$item['changefreq']."</changefreq>\n";
			}

            $map .= "\t\t</url>\n";
        }

        $map .= $this->footer . "\n";
		$this->items = array();
		
		return $map;
	}
	
} 
 
?>