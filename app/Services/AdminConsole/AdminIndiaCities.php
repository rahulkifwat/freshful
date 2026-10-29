<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryScope;
use Illuminate\Support\Facades\DB;

/**
 * Freshful admin console (admin update 2): city choices for a state, so a city is picked from a list instead of typed.
 * Uses the old city master table when the database has one (table "city" with state_id, as the old admin's get_city.php
 * read); otherwise this built-in list of India's district headquarters and major towns. "Other" lets a missing town be typed.
 */
class AdminIndiaCities
{
    public const LIST = [
        'andhra pradesh' => 'Visakhapatnam,Vijayawada,Guntur,Nellore,Kurnool,Kakinada,Rajahmundry,Tirupati,Kadapa,Anantapur,Eluru,Ongole,Vizianagaram,Srikakulam,Machilipatnam,Chittoor,Hindupur,Proddatur,Bhimavaram,Madanapalle,Guntakal,Dharmavaram,Gudivada,Narasaraopet,Tadipatri,Chilakaluripet,Tenali,Amaravati,Nandyal,Adoni',
        'arunachal pradesh' => 'Itanagar,Naharlagun,Pasighat,Tawang,Ziro,Bomdila,Tezu,Aalo,Roing,Namsai',
        'assam' => 'Guwahati,Silchar,Dibrugarh,Jorhat,Nagaon,Tinsukia,Tezpur,Bongaigaon,Dhubri,Diphu,North Lakhimpur,Karimganj,Sivasagar,Goalpara,Barpeta,Golaghat,Hailakandi,Kokrajhar',
        'bihar' => 'Patna,Gaya,Bhagalpur,Muzaffarpur,Purnia,Darbhanga,Bihar Sharif,Arrah,Begusarai,Katihar,Munger,Chhapra,Saharsa,Hajipur,Sasaram,Dehri,Siwan,Motihari,Nawada,Bettiah,Kishanganj,Jamalpur,Buxar,Jehanabad,Aurangabad,Samastipur,Madhubani,Sitamarhi',
        'chhattisgarh' => 'Raipur,Bhilai,Bilaspur,Korba,Durg,Rajnandgaon,Raigarh,Jagdalpur,Ambikapur,Chirmiri,Dhamtari,Mahasamund,Kanker,Janjgir',
        'goa' => 'Panaji,Margao,Vasco da Gama,Mapusa,Ponda,Bicholim,Curchorem,Canacona,Quepem,Valpoi',
        'gujarat' => 'Ahmedabad,Surat,Vadodara,Rajkot,Bhavnagar,Jamnagar,Gandhinagar,Junagadh,Anand,Nadiad,Morbi,Mehsana,Bharuch,Vapi,Navsari,Veraval,Porbandar,Godhra,Bhuj,Gandhidham,Palanpur,Valsad,Surendranagar,Amreli,Dahod,Botad,Patan,Himmatnagar,Kalol,Deesa',
        'haryana' => 'Faridabad,Gurugram,Panipat,Ambala,Yamunanagar,Rohtak,Hisar,Karnal,Sonipat,Panchkula,Bhiwani,Sirsa,Bahadurgarh,Jind,Thanesar,Kaithal,Rewari,Palwal,Hansi,Narnaul,Fatehabad,Gohana,Tohana,Charkhi Dadri,Jhajjar',
        'himachal pradesh' => 'Shimla,Mandi,Solan,Dharamshala,Baddi,Nahan,Palampur,Sundarnagar,Chamba,Una,Kullu,Hamirpur,Bilaspur,Manali,Kangra',
        'jharkhand' => 'Ranchi,Jamshedpur,Dhanbad,Bokaro,Deoghar,Phusro,Hazaribagh,Giridih,Ramgarh,Medininagar,Chirkunda,Chaibasa,Dumka,Sahibganj,Gumla,Lohardaga,Pakur,Godda,Koderma,Chatra',
        'karnataka' => 'Bengaluru,Mysuru,Hubballi,Dharwad,Mangaluru,Belagavi,Kalaburagi,Davanagere,Ballari,Vijayapura,Shivamogga,Tumakuru,Raichur,Bidar,Hosapete,Udupi,Hassan,Chitradurga,Mandya,Kolar,Chikkamagaluru,Gadag,Bagalkot,Karwar,Ramanagara,Chikkaballapur,Hosur Road,Gangavati,Robertsonpet,Bhadravati,Sirsi,Madikeri,Yadgir,Haveri,Koppal,Chamarajanagar,Kanakapura,Anekal,Hoskote,Doddaballapura,Nelamangala',
        'kerala' => 'Thiruvananthapuram,Kochi,Kozhikode,Thrissur,Kollam,Kannur,Alappuzha,Palakkad,Kottayam,Malappuram,Manjeri,Thalassery,Ponnani,Vatakara,Kanhangad,Payyanur,Koyilandy,Kasaragod,Pathanamthitta,Idukki,Kalpetta,Perinthalmanna,Tirur,Changanassery,Muvattupuzha,Aluva,Kayamkulam',
        'madhya pradesh' => 'Indore,Bhopal,Jabalpur,Gwalior,Ujjain,Sagar,Dewas,Satna,Ratlam,Rewa,Murwara,Singrauli,Burhanpur,Khandwa,Bhind,Chhindwara,Guna,Shivpuri,Vidisha,Chhatarpur,Damoh,Mandsaur,Khargone,Neemuch,Pithampur,Hoshangabad,Itarsi,Sehore,Betul,Seoni,Datia,Nagda,Shahdol,Dhar,Mhow',
        'maharashtra' => 'Mumbai,Pune,Nagpur,Thane,Nashik,Kalyan,Vasai-Virar,Aurangabad,Navi Mumbai,Solapur,Mira-Bhayandar,Bhiwandi,Amravati,Nanded,Kolhapur,Ulhasnagar,Sangli,Malegaon,Jalgaon,Akola,Latur,Dhule,Ahmednagar,Chandrapur,Parbhani,Ichalkaranji,Jalna,Ambarnath,Bhusawal,Panvel,Badlapur,Beed,Gondia,Satara,Barshi,Yavatmal,Achalpur,Osmanabad,Nandurbar,Wardha,Udgir,Hinganghat,Ratnagiri,Pimpri-Chinchwad,Baramati,Lonavala',
        'manipur' => 'Imphal,Thoubal,Bishnupur,Churachandpur,Kakching,Ukhrul,Senapati,Tamenglong,Jiribam,Moreh',
        'meghalaya' => 'Shillong,Tura,Jowai,Nongstoin,Williamnagar,Baghmara,Nongpoh,Resubelpara,Mairang',
        'mizoram' => 'Aizawl,Lunglei,Champhai,Serchhip,Kolasib,Lawngtlai,Saiha,Mamit',
        'nagaland' => 'Kohima,Dimapur,Mokokchung,Tuensang,Wokha,Zunheboto,Mon,Phek,Kiphire,Longleng,Peren',
        'odisha' => 'Bhubaneswar,Cuttack,Rourkela,Berhampur,Sambalpur,Puri,Balasore,Bhadrak,Baripada,Jharsuguda,Jeypore,Bargarh,Rayagada,Bhawanipatna,Dhenkanal,Barbil,Kendujhar,Sunabeda,Paradip,Angul,Koraput,Jajpur',
        'punjab' => 'Ludhiana,Amritsar,Jalandhar,Patiala,Bathinda,Mohali,Hoshiarpur,Batala,Pathankot,Moga,Abohar,Malerkotla,Khanna,Phagwara,Muktsar,Barnala,Rajpura,Firozpur,Kapurthala,Sangrur,Fazilka,Gurdaspur,Kharar,Gobindgarh,Mansa,Faridkot,Rupnagar,Zirakpur',
        'rajasthan' => 'Jaipur,Jodhpur,Kota,Bikaner,Ajmer,Udaipur,Bhilwara,Alwar,Bharatpur,Sikar,Pali,Sri Ganganagar,Tonk,Kishangarh,Beawar,Hanumangarh,Dhaulpur,Gangapur City,Sawai Madhopur,Churu,Jhunjhunu,Baran,Chittorgarh,Barmer,Nagaur,Banswara,Bundi,Jaisalmer,Dungarpur,Sirohi,Mount Abu,Dausa,Karauli,Jhalawar,Rajsamand,Pratapgarh,Jalore',
        'sikkim' => 'Gangtok,Namchi,Gyalshing,Mangan,Rangpo,Singtam,Jorethang,Pakyong',
        'tamil nadu' => 'Chennai,Coimbatore,Madurai,Tiruchirappalli,Salem,Tirunelveli,Tiruppur,Vellore,Erode,Thoothukudi,Dindigul,Thanjavur,Ranipet,Sivakasi,Karur,Udhagamandalam,Hosur,Nagercoil,Kanchipuram,Kumarapalayam,Karaikudi,Neyveli,Cuddalore,Kumbakonam,Tiruvannamalai,Pollachi,Rajapalayam,Gudiyatham,Pudukkottai,Vaniyambadi,Ambur,Nagapattinam,Tambaram,Avadi,Krishnagiri,Dharmapuri,Namakkal,Viluppuram,Ariyalur,Perambalur,Tiruvallur,Chengalpattu,Kallakurichi,Tenkasi,Tirupathur,Mayiladuthurai,Ramanathapuram,Sivaganga,Virudhunagar,Theni,Tiruvarur,Jolarpet,Arakkonam,Arcot,Walajapet,Sriperumbudur,Gingee,Mettupalayam,Gobichettipalayam,Palani,Kovilpatti,Srivilliputhur,Pernambut,Natrampalli,Katpadi',
        'telangana' => 'Hyderabad,Warangal,Nizamabad,Karimnagar,Khammam,Ramagundam,Mahbubnagar,Nalgonda,Adilabad,Suryapet,Miryalaguda,Siddipet,Jagtial,Mancherial,Kothagudem,Bodhan,Sangareddy,Medak,Kamareddy,Wanaparthy,Nirmal,Vikarabad,Secunderabad,Gachibowli',
        'tripura' => 'Agartala,Udaipur,Dharmanagar,Kailashahar,Belonia,Khowai,Ambassa,Sabroom,Teliamura',
        'uttar pradesh' => 'Lucknow,Kanpur,Ghaziabad,Agra,Varanasi,Meerut,Prayagraj,Bareilly,Aligarh,Moradabad,Saharanpur,Gorakhpur,Noida,Greater Noida,Firozabad,Jhansi,Muzaffarnagar,Mathura,Ayodhya,Rampur,Shahjahanpur,Farrukhabad,Mau,Hapur,Etawah,Mirzapur,Bulandshahr,Sambhal,Amroha,Hardoi,Fatehpur,Raebareli,Orai,Sitapur,Bahraich,Modinagar,Unnao,Jaunpur,Lakhimpur,Hathras,Banda,Pilibhit,Barabanki,Khurja,Gonda,Mainpuri,Lalitpur,Etah,Deoria,Badaun,Ghazipur,Sultanpur,Azamgarh,Bijnor,Basti,Ballia,Chandausi,Akbarpur,Kannauj,Shamli',
        'uttarakhand' => 'Dehradun,Haridwar,Roorkee,Haldwani,Rudrapur,Kashipur,Rishikesh,Pithoragarh,Ramnagar,Kotdwar,Almora,Nainital,Mussoorie,Srinagar,Tehri',
        'west bengal' => 'Kolkata,Howrah,Asansol,Siliguri,Durgapur,Bardhaman,Malda,Baharampur,Habra,Kharagpur,Shantipur,Dankuni,Dhulian,Ranaghat,Haldia,Raiganj,Krishnanagar,Nabadwip,Medinipur,Jalpaiguri,Balurghat,Basirhat,Bankura,Chakdaha,Darjeeling,Alipurduar,Purulia,Jangipur,Bolpur,Bangaon,Cooch Behar,Tamluk,Barasat,Kalyani,Salt Lake',
        'andaman and nicobar islands' => 'Port Blair,Car Nicobar,Mayabunder,Diglipur,Rangat,Havelock',
        'chandigarh' => 'Chandigarh',
        'dadra and nagar haveli and daman and diu' => 'Daman,Diu,Silvassa',
        'dadra and nagar haveli' => 'Silvassa',
        'daman and diu' => 'Daman,Diu',
        'delhi' => 'New Delhi,Delhi,Dwarka,Rohini,Saket,Karol Bagh,Lajpat Nagar,Janakpuri,Pitampura,Vasant Kunj,Mayur Vihar,Laxmi Nagar,Shahdara,Narela',
        'jammu and kashmir' => 'Srinagar,Jammu,Anantnag,Baramulla,Sopore,Kathua,Udhampur,Punch,Rajouri,Kupwara,Pulwama,Budgam,Ganderbal,Doda,Kishtwar,Samba,Reasi,Ramban,Kulgam,Shopian,Bandipora',
        'ladakh' => 'Leh,Kargil',
        'lakshadweep' => 'Kavaratti,Agatti,Minicoy,Andrott',
        'puducherry' => 'Puducherry,Karaikal,Mahe,Yanam,Ozhukarai',
        'pondicherry' => 'Puducherry,Karaikal,Mahe,Yanam,Ozhukarai',
    ];

    private static function norm(string $s): string { return strtolower(trim(preg_replace('/\s+/', ' ', str_replace('&', 'and', $s)))); }

    public static function forState(int $stateId): array
    {
        $have = DB::table('cities')->where('state_id', $stateId)->pluck('city')->map(fn ($c) => self::norm((string) $c))->all();
        $names = []; $source = 'built-in list';
        if (CountryScope::has('city') && CountryScope::has('city', 'state_id')) {
            $col = CountryScope::has('city', 'name') ? 'name' : (CountryScope::has('city', 'city') ? 'city' : null);
            if ($col) { $names = DB::table('city')->where('state_id', $stateId)->orderBy($col)->limit(3000)->pluck($col)->map(fn ($c) => trim((string) $c))->filter()->all(); $source = 'city master table'; }
        }
        if (!$names) {
            $st = DB::table('states')->where('id', $stateId)->value('name');
            $list = self::LIST[self::norm((string) $st)] ?? '';
            $names = $list !== '' ? explode(',', $list) : [];
            sort($names, SORT_NATURAL | SORT_FLAG_CASE);
        }
        $out = [];
        foreach (array_values(array_unique($names)) as $n) $out[] = ['name' => $n, 'added' => in_array(self::norm($n), $have, true)];
        return ['ok' => true, 'cities' => $out, 'source' => $source];
    }
}
