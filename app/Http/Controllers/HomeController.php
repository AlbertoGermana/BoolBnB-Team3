<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use App\Service;
use App\Apartment;
use App\User;
use App\Message;
use App\View;
use Carbon\Carbon;
use JavaScript;

// file UploadTrait creato da noi dentro la cartella creata da noi Traits per fare l'upload delle immagini
use App\Traits\UploadTrait;

class HomeController extends Controller
{
  use UploadTrait;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
      $apartments = Apartment::all();
      $user_id = auth()->user()->id;
      $messages = Message::all();
      $user_apartments = $apartments -> where('user_id',$user_id);
      $users_messages_grouped_by_apartment = [];
      foreach ($user_apartments as $apartment) {
        $users_messages_grouped_by_apartment[] = $messages -> where('apartment_id', $apartment -> id);
      }
      // dd($users_messages_grouped_by_apartment);
      return view('home', compact('user_apartments', 'users_messages_grouped_by_apartment'));
    }

    public function createApartment()
    {
      $services = Service::all();
      return view('createApartment', compact('services'));
    }

    public function storeApartment(Request $request){
      $validateData = $request -> validate([
        'title' => 'required | string',
        'image' => 'required | image | mimes:jpeg,png,jpg,gif | max:2048',
        'description' => 'required | string',
        'rooms_n' => 'required | integer | min:1',
        'beds_n' => 'required | integer | min:1',
        'bathrooms_n' => 'required | integer | min:0',
        'square_meters' => 'required | integer | min:4',
        'services' => 'required',
        'address' => 'required | string',
        "lat" => 'required | string',
        "lon" => 'required | string',
        'is_active' => 'required | boolean'
      ]);
      $apartment = new Apartment;
      $apartment -> title = $validateData['title'];
      // $apartment -> image = $validateData['image'];
      $apartment -> description = $validateData['description'];
      $apartment -> rooms_n = $validateData['rooms_n'];
      $apartment -> beds_n = $validateData['beds_n'];
      $apartment -> bathrooms_n = $validateData['bathrooms_n'];
      $apartment -> square_meters = $validateData['square_meters'];
      $apartment -> address = $validateData['address'];
      $apartment -> lat = $validateData['lat'];
      $apartment -> lon = $validateData['lon'];
      $apartment -> is_active = $validateData['is_active'];
      $apartment -> user_id = auth()->user()->id;

      // Check if a profile image has been uploaded
      if ($request->has('image')) {
          // Get image file
          $image = $request->file('image');
          // Make a image name based on apartment title and current timestamp
          $name = Str::slug($request->input('title'), '-').'_'.time();
          // Define folder path
          $folder = '/uploads/images/';
          // Make a file path where image will be stored [ folder path + file name + file extension]
          $filePath = $folder . $name. '.' . $image->getClientOriginalExtension();
          // Upload image
          $this->uploadOne($image, $folder, 'public', $name);
          // Set user profile image path in database to filePath
          $apartment -> image = $filePath;
      }

      $apartment -> save();
      $apartment -> services() -> sync($validateData['services']);
      return redirect() -> route("showApartment",$apartment['id'])
                        -> withSuccess("Appartamento creato correttamente");
    }

    public function editApartment($id){
      $apartment = Apartment::findOrFail($id);
      $services = Service::all();
      return view('editApartment', compact("apartment", "services"));
    }

    public function updateApartment(Request $request, $id){
      $validateData = $request -> validate([
        'title' => 'required | string',
        'image' => 'nullable | image | mimes:jpeg,png,jpg,gif | max:2048',
        'description' => 'required | string',
        'rooms_n' => 'required | integer | min:1',
        'beds_n' => 'required | integer | min:1',
        'bathrooms_n' => 'required | integer | min:0',
        'square_meters' => 'required | integer | min:4',
        'services' => 'required',
        'address' => 'required | string',
        "lat" => 'required | string',
        "lon" => 'required | string',
        'is_active' => 'required | boolean'
      ]);
      $apartment = Apartment::findOrFail($id);
      $apartment -> title = $validateData['title'];
      $apartment -> description = $validateData['description'];
      $apartment -> rooms_n = $validateData['rooms_n'];
      $apartment -> beds_n = $validateData['beds_n'];
      $apartment -> bathrooms_n = $validateData['bathrooms_n'];
      $apartment -> square_meters = $validateData['square_meters'];
      $apartment -> address = $validateData['address'];
      $apartment -> lat = $validateData['lat'];
      $apartment -> lon = $validateData['lon'];
      $apartment -> is_active = $validateData['is_active'];

      $oldImage = public_path($apartment -> image);

      // Check if a profile image has been uploaded
      if ($request->has('image')) {
          // Get image file
          $image = $request->file('image');
          // Make a image name based on apartment title and current timestamp
          $name = 'apartment'.Str::slug($apartment -> id, '-').'_'.time();
          // Define folder path
          $folder = '/uploads/images/';
          // Make a file path where image will be stored [ folder path + file name + file extension]
          $filePath = $folder . $name. '.' . $image->getClientOriginalExtension();

          // PRIMA DI SALVARE L'APPARTAMENTO CANCELLO IN LOCALE LA VECCHIA IMMAGINE
          File::delete($oldImage);

          // Upload image
          $this->uploadOne($image, $folder, 'public', $name);
          // Set user profile image path in database to filePath
          $apartment -> image = $filePath;
      }
      $apartment -> save();
      $apartment -> services() -> sync($validateData['services']);

      return redirect() -> route("showApartment", $id)
                        -> withSuccess("Appartamento ".$validateData["title"]
                            ." aggiornato correttamente");
    }

    public function deleteApartment($id){
      $apartment = Apartment::findOrFail($id);
      $deleteImage = File::delete(public_path($apartment -> image));
      $apartment -> delete();
      return redirect() -> route("welcome")
                        -> withSuccess("Appartamento eliminato con successo!");
    }
    public function showApartmentStatistics($apartment_id)
    {
      //soluzione spiegata https://stackoverflow.com/questions/5667888/counting-the-occurrences-frequency-of-array-elements
      // soluzione https://jsfiddle.net/simevidas/bnACW/
      $views = View::all() -> where('apartment_id', $apartment_id); //restituisce array[] con all'interno ogni singola view dell'appartamento
      $messages = Message::all() -> where('apartment_id', $apartment_id); //restituisce array[] con all'interno ogni singolo messaggio per l'appartamento

      // Gestione delle informazioni per le visualizzazioni
      $viewsFiltered = [];
      foreach ($views as $view) {
        $viewsFiltered[] = date( "m",  strtotime( $view -> created_at) );
      }
      $viewsMonths = []; // restituirà l'array con i mesi in ordine
      $viewsCount = []; // restituirà l'array le occorrenze di ciascun mese
      $prev = 0;

      sort($viewsFiltered);
      for ($i = 0; $i < count($viewsFiltered); $i++ ) {
          if ( $viewsFiltered[$i] !== $prev ) {
              $viewsMonths[] = $viewsFiltered[$i];
              $viewsCount[] = 1;
          } else {
              $viewsCount[count($viewsCount) - 1]++;
          }
          $prev = $viewsFiltered[$i];
      }

      // Gestione delle informazioni per i Messaggi
      $messagesFiltered = [];
      foreach ($messages as $message) {
        $messagesFiltered[] = date( "m",  strtotime( $message -> created_at) );
      }
      $messagesMonths = []; // restituirà l'array con i mesi in ordine
      $messagesCount = []; // restituirà l'array le occorrenze di ciascun mese
      $prev = 0;

      sort($messagesFiltered);
      for ($i = 0; $i < count($messagesFiltered); $i++ ) {
          if ( $messagesFiltered[$i] !== $prev ) {
              $messagesMonths[] = $messagesFiltered[$i];
              $messagesCount[] = 1;
          } else {
              $messagesCount[count($messagesCount) - 1]++;
          }
          $prev = $messagesFiltered[$i];
      }

      function trasformaMesi($array){
        for ($i=0; $i < count($array); $i++) {
          if($array[$i] == '01'){
            $array[$i] = 'Gen';
          }elseif ($array[$i] == '02'){
            $array[$i] = 'Feb';
          }elseif ($array[$i] == '03'){
            $array[$i] = 'Mar';
          }elseif ($array[$i] == '04'){
            $array[$i] = 'Apr';
          }elseif ($array[$i] == '05'){
            $array[$i] = 'Mag';
          }elseif ($array[$i] == '06'){
            $array[$i] = 'Giu';
          }elseif ($array[$i] == '07'){
            $array[$i] = 'Lug';
          }elseif ($array[$i] == '08'){
            $array[$i] = 'Ago';
          }elseif ($array[$i] == '09'){
            $array[$i] = 'Set';
          }elseif ($array[$i] == '10'){
            $array[$i] = 'Ott';
          }elseif ($array[$i] == '11'){
            $array[$i] = 'Nov';
          }elseif ($array[$i] == '12'){
            $array[$i] = 'Dic';
        }
      }
      return $array;
    }
      $viewsMonths = trasformaMesi($viewsMonths);
      $messagesMonths = trasformaMesi($messagesMonths);

      JavaScript::put([ // questa classe trasferisce i dati al javascript
      'viewsCount' => $viewsCount,
      'viewsMonths' => $viewsMonths,
      'messagesMonths' => $messagesMonths,
      'messagesCount' => $messagesCount,
      ]);

      // return view('showApartmentStatistics', compact("views", "messages"));
      return view('showApartmentStatistics');
    }
}
