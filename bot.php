<?php
error_reporting(0);

/*
Veritabanı bağlantısı yapıyoruz. mysqli_connect içeriği sırasıyla
localhost, kullanıcı adı, şifre ve veritabanı ismi
*/
$conn = mysqli_connect('localhost', 'root', '', 'dbname');
if(mysqli_connect_errno()){
	echo 'Error : Veritabani baglantisi yok.';
} 
mysqli_query($conn, "SET NAMES 'utf8'");


/*
iki belirteç arasındaki veriyi okumaya yarayan bolum_sec
fonksiyonu. Ayrıştırma yapılacak yerin başlangıcını
başlangıç değişkeniylle, son yerini  de son değişkeni ile
tanımlıyoruz. Hangi değişkenden ayrıştırma yapılacaksa onu da
değişken isimli değişkenle tanımlıyoruz. 
*/
function bolum_sec($baslangic, $son, $degisken){
	@preg_match_all('/' . preg_quote($baslangic, '/') .
	'(.*?)'. preg_quote($son, '/').'/i', $degisken, $m);
	return @$m[1];
}


/*
Maçkolikten gelen tarih bilgisini veritabanına kaydeliecek
halde yani YYYY-mm-dd formatına çeviriyoruz. 
*/
function tarih_format($date){
	
	$date = explode('.', $date);
	$date = $date[2] . '-'. $date[1] . '-' . $date[0];
	return $date;
}

/*
Veri çekilecek sayfanın linkini yazıyoruz. 
*/
$link_mackolik = 'http://arsiv.mackolik.com/Iddaa-Programi';


/*
CURL işlemlerini hazırlıyoruz. Yukarıdaki linke gidip
sayfayı getirme işlemleri
*/
$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $link_mackolik);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, '');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$server_output = curl_exec ($ch);

	curl_close ($ch);
	
	//echo $server_output;

/*
html kodları arasındaki id="iddaa-tab-body" ifadesinden bölüyoruz
ve ikinci yarısın alıyoruz. 
*/
$veri = explode('id="iddaa-tab-body"', $server_output);
$veri = $veri[1];


/*
html kodlarını iddaa-tab-body2 ifadesinden  bölüyoruz ve 
ilk yarısını alıyoruz. 
*/
$veri = explode('iddaa-tab-body2', $veri);
$veri = $veri[0];


/*
ligleri birbirinden ayırmak için class="iddaa-oyna-title" ifadesiyle
bölüyoruz. Bu bölme işlemini döngüye sokup verileri ayrıştıracağız.
*/
$ligler = explode('class="iddaa-oyna-title"', $veri);
$ligler_count = count($ligler);




for($i=1; $i<=$ligler_count; $i++){
	
	$lig =  '<table><tr class="iddaa-oyna-title"' . $ligler[$i] . '</table>';
	
	$lig_country = explode('</tr>', $lig);
	$lig_country = strip_tags($lig_country[0]);
	$lig_country = trim($lig_country);
	$lig_countries = explode(' ', $lig_country);
	$country = $lig_countries[0];
	$kupa = $lig_country;
	echo 'ÜLKE : ' . $country . ' / ' . $kupa;
	echo '<br />';

	$maclar = explode('Tr2', $lig);
	$maclar_count = count($maclar);


	for($m = 0; $m<$maclar_count; $m++){

		//echo $maclar[$m];
		$mac = $maclar[$m];
		
		// Tarih bölümü alınıyor
		$tarih = bolum_sec("rateSort=\"tarih_1\"","</td>",$lig);
		$tarih = str_replace('>', '', $tarih[0]);
		$tarih = tarih_format($tarih);

		
		
		$saat = bolum_sec("width=\"45\"", "</td>", $mac);
		$saat = str_replace('align="center">', '', $saat[0]);
		
		// Ev sahibi ve misafir takım belirleniyor. 
		$home = bolum_sec("popTeam", "<span", $mac);
		$home_team = explode('>', $home[0]);
		$home_team = trim($home_team[1]);
		
		// Misafir Takım belirleniyor
		$away_team = explode('>', $home[1]);
		$away_team = trim($away_team[1]);
		
		// MS 1 Belirleniyor
		$ms1 = bolum_sec("MS1\">", "</a>", $mac);
		$ms1 = trim($ms1[0]);
		
		// MS X belirleniyor
		$msx = bolum_sec("MSX\">", "</a>", $mac);
		$msx = trim($msx[0]);
		
		// MS 2 belirleniyor
		$ms2 = bolum_sec("MS2\">", "</a>", $mac);
		$ms2 = trim($ms2[0]);
		
		// GOL 1 Belirleniyor
		$golalt = bolum_sec("AU1\">", "</a>", $mac);
		$golalt = trim($golalt[0]);
		
		// GOL 2 Belirleniyor
		$golust = bolum_sec("AU2\">", "</a>", $mac);
		$golust = trim($golust[0]);
		
		// CS 1-X Belirleniyor
		$cs1_x = bolum_sec("CS1-X\">", "</a>", $mac);
		$cs1_x = trim($cs1_x[0]);
		
		// CS 1-2 Belirleniyor
		$cs1_2 = bolum_sec("CS1-2\">", "</a>", $mac);
		$cs1_2 = trim($cs1_2[0]);
		
		// CS X-2 Belirleniyor
		$csx_2 = bolum_sec("CSX-2\">", "</a>", $mac);
		$csx_2 = trim($csx_2[0]);
		
		
		if($home_team !=''){
			//echo $tarih . ' / ' . $saat . ' / ' . $home_team . ' - ' . $away_team . ' / MS1 : ' . $ms1 . ' / MSX : ' . $msx . ' / MS 2 : ' . $ms2 . ' / GOL ALT : ' . $golalt . ' / GOL ÜST : ' . $golust . ' /  CS 1-X ' . $cs1_x . ' / CS 1-2 : ' . $cs1_2 . ' / CS X-2 : ' . $csx_2; 
			
			/*
			Tarih, ev sahibi takım, misafir takım ile eşleşen kayıt var mı onu sorguluyoruz. Daha önce kaydedilip 
			kaydedilmediğini anlamak için. 
			*/
			$eski_sql =	"	SELECT * FROM apimatchs WHERE Date ='$tarih' AND HomeTeam ='$home_team' AND AwayTeam = '$away_team' ";
			$eski = mysqli_query($conn, $eski_sql);
			$eski_count = mysqli_num_rows($eski);
				
				
			/*
			Eğer daha önce kaydedilmemiş ise kaydediyoruz. 
			*/
			if($eski_count == 0){
				
				$mac_kaydet = mysqli_query($conn, "INSERT INTO apimatchs (Lig, Date, Saat, HomeTeam, AwayTeam, MS1, MSX, MS2, gol_alt, gol_ust, cs1x, cs12, cs2x, Canli) VALUES 
				('$kupa', '$tarih', '$saat', '$home_team', '$away_team', '$ms1', '$msx', '$ms2', '$golalt', '$golust', '$cs1_x', '$cs1_2', '$csx_2', '0') ");
			} else {
				/*
				Daha önce kaydedilmiş ise güncelleme işllemi yapıyoruz. 
				
				*/
				$mac_guncelle = mysqli_query($conn, "UPDATE apimatchs SET MS1 = '$ms1', MSX = '$msx', MS2 = '$ms2, gol_alt = '$golalt', gol_ust = '$golust', cs1x = '$cs1_x', cs12 = '$cs1_2', cs2x = '$csx_2' WHERE Date ='$tarih' AND HomeTeam ='$home_team' AND AwayTeam = '$away_team'  " );
			}
			
			
			
		}
		
		
		
	}
}








?>
