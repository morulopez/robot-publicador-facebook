<?php

class PublicFacebook{
  public $mysql;

  function __construct(){
    include "simple_html_dom.php";
    $this->mysql = new mysqli('127.0.0.1', 'root', 'Jesus17121987.', 'scrapperdatabase');
  }

  function publickFacebookGroup(){
    /** 
     * SI la fecha de login de login es menor que hoy que haga el login si no que obtenga el login 
     * 
    */
    $getBlogPublished = $this->mysql->query("SELECT * FROM contenido_publicado_en_blog ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

    $facebook_session = $this->mysql->query("SELECT * FROM facebook_session ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
    foreach ($facebook_session as $key => $value) {
      $contentGroupsPublished = $this->mysql->query("SELECT *,cgp.id as content_group_id,fg.id_group FROM content_groups_published cgp LEFT JOIN facebook_groups fg ON fg.id = cgp.id_facebook_group WHERE fg.id_facebook_sesion = {$facebook_session[$key]['id']} AND id_content_publicado={$getBlogPublished[0]['id']}")->fetch_all(MYSQLI_ASSOC);
      $facebook_session[$key]['contentGroupsPublished'] = $contentGroupsPublished;
    }
    $breakForeach = false;
    foreach($facebook_session as $sessionfacebook ){

        $sessionOk = $this->compararFechas($sessionfacebook["last_date"]);
        if(!$sessionOk){
          $newSession = $this->getPassword($sessionfacebook["email_cuenta_facebook"]);
          $this->mysql->query(" UPDATE facebook_session SET datr='{$newSession['datr']}', sb='{$newSession['sb']}', xs='{$newSession['xs']}', fr='{$newSession['fr']}',c_user='{$newSession['c_user']}',last_date=NOW() WHERE id={$sessionfacebook['id']} ");
          $sessionfacebook['datr'] = $newSession['datr'];
          $sessionfacebook['sb'] = $newSession['sb'];
          $sessionfacebook['xs'] = $newSession['xs'];
          $sessionfacebbook['fr'] = $newSession['fr'];
          $sessionfacebook['c_user']= $newSession['c_user'];
        }
        foreach($sessionfacebook['contentGroupsPublished'] as $contentGroupsPublished){
          if(!$contentGroupsPublished['published']){
            $this->publicarEnfacebook($contentGroupsPublished['id_group'],$sessionfacebook['datr'],$sessionfacebook['sb'],$sessionfacebook['xs'],$sessionfacebook['fr'],$sessionfacebook['c_user'],$getBlogPublished[0]);
            $this->mysql->query("UPDATE content_groups_published SET published=1 WHERE id={$contentGroupsPublished['content_group_id']} ");
            $breakForeach = true;
            break;
          }
        }
        if($breakForeach){
          break;
        }
    }
  }

  function compararFechas($fecha){
    $now = strtotime("now");
    $lastsession = strtotime($fecha);
    $compare = $now - $lastsession;
    if($compare>=86400){
      return false;
    }
    return true;
  }

  function getPassword($email){
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://www.facebook.com/",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "authority: www.facebook.com",
        "cache-control: max-age=0",
        "upgrade-insecure-requests: 1",
        "user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.125 Safari/537.36",
        "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
        "sec-fetch-site: none",
        "sec-fetch-mode: navigate",
        "sec-fetch-user: ?1",
        "sec-fetch-dest: document",
        "accept-language: en-GB,en-US;q=0.9,en;q=0.8,es;q=0.7",
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);


    $html = new simple_html_dom();
    $data = $html->load($response);
    $script = $data->find("body",0)->find("script",11);
    $jazoest = $data->find("form",0)->find("input",0)->value;
    $lsd = $data->find("form",0)->find("input",1)->value;
    $action = $data->find("form",0)->action;
    $dom = new DOMDocument();
    $dom->loadHTML($script);  //convert character asing
    $xpath = new DOMXPath($dom);  
    $script = $xpath->query ('//script[contains(text(),"sources:")]')->item (0)->nodeValue;
    $script2 = explode(",",$script);
    $found = false;
    $valorcookie="";
    foreach($script2 as $sc){
      if($found){
        $valorcookie=$sc;
        break;
      }
      if($sc=='["_js_datr"'){
        $found=true;
      }
    }

    $variablecookie = explode('"',$valorcookie)[1];

    ///////AHORA PEDIMOS LA COOKIE POR SI EN EL BACKEND NO SE HA

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://www.facebook.com/cookie/consent/",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_HEADER => 1,
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "accept_consent=true&=&=&=&=&=&=&=&=&=&=&=&=&=&lsd=".$lsd."&jazoest=".$jazoest."&=&=&=",
      CURLOPT_HTTPHEADER => array(
        "authority: www.facebook.com",
        "user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.125 Safari/537.36",
        "content-type: application/x-www-form-urlencoded",
        "accept: */*",
        "origin: https://www.facebook.com",
        "sec-fetch-site: same-origin",
        "sec-fetch-mode: cors",
        "sec-fetch-dest: empty",
        "referer: https://www.facebook.com/login",
        "accept-language: en-GB,en-US;q=0.9,en;q=0.8,es;q=0.7",
        "cookie: _js_datr={$variablecookie}"
      ),
    ));

    $output = curl_exec($curl);

    curl_close($curl);
    $headers = [];
    $output = rtrim($output);
    $data = explode("\n",$output);
    $data = explode(";",$data[1]);
    $datr = explode("=",$data[0]);


    $curl2 = curl_init();

    curl_setopt_array($curl2, array(
      CURLOPT_URL => "http://localhost:9429/v1/app-node/login-facebook",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "jazoest={$jazoest}&lsd={$lsd}&datr={$datr[1]}&email={$email}&action={$action}",
      CURLOPT_HTTPHEADER => array(
        "Content-Type: application/x-www-form-urlencoded"
      ),
    ));

    $response2 = curl_exec($curl2);

    curl_close($curl2);
    $data = json_decode($response2);

    $sb = explode(";",$data[0]);
    $sb = explode("=",$sb[0]);
    $c_user = explode(";",$data[1]);
    $c_user = explode("=",$c_user[0]);
    $xs = explode(";",$data[2]);
    $xs = explode("=",$xs[0]);
    $fr = explode(";",$data[3]);
    $fr = explode("=",$fr[0]);
      return ["sb"=>$sb[1],"c_user"=>$c_user[1],"xs"=>$xs[1],"fr"=>$fr[1],"datr"=>$datr[1]];
  }

  function publicarEnfacebook($id_facebook_group,$datr,$sb,$xs,$fr,$c_user,$BlogPublished){

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => "https://m.facebook.com/groups/{$id_facebook_group}/?ref=group_browse",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
          "authority: m.facebook.com",
          "cache-control: max-age=0",
          "upgrade-insecure-requests: 1",
          "user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.193 Safari/537.36",
          "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
          "sec-fetch-site: same-origin",
          "sec-fetch-mode: navigate",
          "sec-fetch-user: ?1",
          "sec-fetch-dest: document",
          "accept-language: es-ES,es;q=0.9",
          "cookie: datr=".$datr.";sb=".$sb.";locale=es_LA;c_user=".$c_user.";xs=".$xs.";fr=".$fr.";m_pixel_ratio=1;wd=1905x567"
        ),
      ));

      $response = curl_exec($curl);
      curl_close($curl);

      $html = new simple_html_dom();
      $data = $html->load($response);
      $fb_dtsg = $data->find("form",0)->find("input",0)->value;
      $jazoestform = $data->find("form",0)->find("input",1)->value;

  

      $urlSend = 'rating=0&message='.$BlogPublished['urlblog'].'&attachment[params][urlInfo][canonical]='.$BlogPublished['urlblog'].'&attachment[params][urlInfo][final]='.$BlogPublished['urlblog'].'&attachment[params][urlInfo][user]='.$BlogPublished['urlblog'].'&attachment[params][favicon]='.$BlogPublished['urlblog'].'&attachment[params][title]='.$BlogPublished['title'].'&attachment[params][summary]='.$BlogPublished['summary'].'&attachment[params][ranked_images][images][0]='.$BlogPublished['urlimagen'].'&attachment[params][ranked_images][ranking_model_version]=11&attachment[params][ranked_images][specified_og]=1&attachment[params][medium]=104&attachment[params][url]='.$BlogPublished['urlblog'].'&attachment[params][extra][src]=&attachment[params][extra][title]=&attachment[params][extra][artist]=&attachment[params][extra][album]=&attachment[params][extra][type]=&attachment[params][external_img]={"src":"'.$BlogPublished['urlimagenshort'].'","width":1026,"height":633}&attachment[type]=100&group_id='.$id_facebook_group.'8&ch=&linkUrl='.$BlogPublished['urlblog'].'&album_fbid=0&fs=&referrer=group&cver=ocelot&unpublished_content_type=0&scheduled_year=&scheduled_month=&scheduled_day=&scheduled_hours=&scheduled_minutes=&scheduled_am_pm=&is_backdated=&backdated_year=&backdated_month=&backdated_day=&background_upload=1&at=&npn=&iscurrent=&npp=&npw=&npa=&npz=&freeform_tag_place=&npc=&loc={}&[0]=&text_[0]=&ogaction=&ogobj=&ogphrase=&ogicon=&oghideattachment=&ogsuggestionmechanism=&[1]=&text_[1]=&source_loc=composer_group&text_format_preset_id=&
      sid=&appid=&internal_extra=&link_no_change=&waterfall_source=composer_group&=Publicar&m_sess=&fb_dtsg='.$fb_dtsg.'&__csr=&__user={$c_user}';

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => "https://m.facebook.com/a/group/post/add/?gid=".$id_facebook_group,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $urlSend,
        CURLOPT_HTTPHEADER => array(
          "authority: m.facebook.com",
          "x-requested-with: XMLHttpRequest",
          "user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.125 Safari/537.36",
          "x-response-format: JSONStream",
          "content-type: application/x-www-form-urlencoded",
          "accept: */*",
          "origin: https://m.facebook.com",
          "sec-fetch-site: same-origin",
          "sec-fetch-mode: cors",
          "sec-fetch-dest: empty",
          "referer: https://m.facebook.com/groups/".$id_facebook_group."?soft=composer",
          "accept-language: en-GB,en-US;q=0.9,en;q=0.8,es;q=0.7",
          "cookie:datr={$datr}; sb={$sb}; m_pixel_ratio=1; locale=es_LA; c_user={$c_user}; xs={$xs}; fr={$fr};wd=1905x567"
        ),
      ));

      $response = curl_exec($curl);
      curl_close($curl);
      return $response;
    }

    function buildUrl($url){
      $urlblog = explode("/",$url);
      $urlblogBuild = '';
      $keys = array_keys($urlblog);
      foreach($urlblog as $key => $partUrl){

          if($key==0){
              $urlblogBuild.='\"'.$partUrl.'\\\/\\\/';
          }elseif($key === end($keys)){
              $urlblogBuild.=$partUrl;
          }
          else{
              $urlblogBuild.=$partUrl.'\\\/';
          }
      }
      return $urlblogBuild;
    }


}

$publicFacebook = new PublicFacebook();

echo $publicFacebook->publickFacebookGroup();
return;
