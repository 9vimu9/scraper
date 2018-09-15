<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Yangqi\Htmldom\Htmldom;

class ModernBrandsProduct extends Model
{
    private $logfile = '/feeds/logs/ModernBrands.log';
    private $baseUrl='https://modernbrands.com.au/product/';
    private $cookie_file='/feeds/ModernBrand/cookie.txt';

    public function cleanPreviousImport()
    {

        $xml_file = '/feeds/ModernBrand/UpdatedProducts.xml';

        if (Storage::exists($this->cookie_file)) {
            Storage::delete($this->cookie_file);
        }

        if (Storage::exists($xml_file)) {
            Storage::delete($xml_file);
        }

        $message = "**********************************************************\n";
        $message .= "ModernBrand Feed started at: ".Carbon::now()->toDayDateTimeString()."\n";
        $message .= "**********************************************************\n";

        // Store message to log and inform user
        Storage::append($this->logfile, $message);
       // echo $message;

        return;
    }

    /**
     * Login to the WASS Website and return the page HTML
     */
    private function getModernBrandPage($url)
    {
        $username = '210400';
       // $username = env('MODERNBRAND_USERNAME');
       // $password = env('MODERNBRAND_PASSWORD');
        $password = 'D3AXD3fopAvXnHVMiN';
        $ckfile = storage_path().'/app/feeds/w/cookie.txt';
        $useragent = "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.4 (KHTML, like Gecko) Chrome/5.0.375.125 Safari/533.4";

        // Create cookie file
        Storage::put($this->cookie_file, '');

        // Fetch the viewstate and event validation
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);

        try {
            // Get login page
            $html = curl_exec($ch);
            curl_close($ch);
        }
        catch (Exception $exception)
        {
            $message = "Caught exception: ".$exception->getMessage()."\n\n";
            Storage::append($this->logfile, $message);
          //  echo $message;
            return '';
        }
        // Start the login process
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);

        // Collecting all POST fields
        $postfields = array();
        $postfields['IBS_RESEQUENCE_ACTION'] = "";
        $postfields['IBS_RESEQUENCE_COLUMN'] = "";
        $postfields['ACTION_SIGNON'] = "";
        $postfields['IBS_LINKSELECTION_ACTION'] = "";
        $postfields['IBS_LINKSELECTION_VALUE'] = "";
        $postfields['IBS_LISTSELECTION_VALUE'] = "";
        $postfields['CatalogueUpdate'] = "";
        $postfields['submitPopupQty'] = "";
        $postfields['submitPopupUnit'] = "";
        $postfields['submitPopupAlias'] = "";
        $postfields['ajaxLoaderSmalImgUrl'] = "https://d4iqe7beda780.cloudfront.net/resources/site/mb/ajaxloader.gif";
        $postfields['ajaxLoaderImgUrl'] = "https://d4iqe7beda780.cloudfront.net/resources/site/mb/ajaxloader.gif";
        $postfields['email'] = $username;
        $postfields['User'] = $username;
        $postfields['Password'] = $password;

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

        try {
            // Get result after login page.
            $ret = curl_exec($ch);
            curl_close($ch);
            return $ret;
        }
        catch (Exception $exception)
        {
            $message = "Caught exception: ".$exception->getMessage()."\n\n";
            Storage::append($this->logfile, $message);
          //  echo $message;
            return '';
        }

        return '';

    }

//    public function updateModernBrandProduct(ModernBrandsProduct $product)
    public function updateModernBrandProduct($product)
    {
        $message = '';

            $url = $this->baseUrl.$product['sku'];
            $result = $this->getModernBrandPage($url);

            if($result != '')
            {
                $html = new Htmldom($result);
            }
            else
            {
                $message .= "(".$product['sku'].") ".$product['name']." could not be updated.  \n";
                return;
            }
dd($html->find('.IBSPageTitleText', 0));
                // Check for change in name
                $product_name = $html->find('.IBSPageTitleText', 0)->plaintext;

                if($product['name'] != $product_name)
                {
                    $change = true;
                    $message .= "**Name** updated to *".$product_name."*  \n";
                    $product['name'] = $product_name;
                }

                // Check for change in stock level
        $in_stock = count($html->find('.IBSPageTitleText'));
        $not_in_stock = count($html->find('.IBSAvailabilityCellShortage'));

                if($in_stock)
                {
                    $product['qty']="Y";
                }
                else
                {
                    $product['qty']="N";
                }




                    $message = "(".$product['sku'].") ".$product['name']." updated:  \n".$message;





        if ($message != '')
        {
            // Store message to log and inform user
            Storage::append($this->logfile, $message);
         //   echo $message;
        }

        return $product;
    }

}
