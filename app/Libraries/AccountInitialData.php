<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Firm;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\TaskTime;
use App\Models\User;

class AccountInitialData {
    private $uuid;
    private $user;
    private $mappedValues = [];
    public function __construct($uuid)
    {
        $this->uuid = $uuid;
        
        $firm = Firm::where("uuid", $this->uuid)->first();
        if(!$firm)
            throw new Exception(__("Firm does not exists"));
        
        $this->user = User::withoutGlobalScope("uuid")
            ->where("firm_id", $firm->id)
            ->where("owner", 1)
            ->first();
    }
    
    public function setInitial()
    {
        if(env("APP_ENV") == "testing")
            return;
    
        if(Auth::onceUsingId($this->user->id))
        {
            DB::transaction(function () {
                $customer = new Customer;
                $customer->role = Customer::ROLE_CUSTOMER;
                $customer->type = Customer::TYPE_PERSON;
                $customer->name = "Jan Kowalski";
                $customer->street = "Przykładowa";
                $customer->house_no = "12";
                $customer->apartment_no = "3C";
                $customer->city = "Warszawa";
                $customer->zip = "01-034";
                $customer->country = "PL";
                $customer->save();
                
                $phone = new CustomerContact;
                $phone->customer_id = $customer->id;
                $phone->type = CustomerContact::TYPE_PHONE;
                $phone->prefix = "+48";
                $phone->val = "777-888-555";
                $phone->save();
                
                $place = new Project;
                $place->name = "Przykładowe miejsce";
                $place->location = "Dostęp do pomieszczeń gospodarczych po wcześniejszym uzgodnieniu z portierem.";
                $place->description = "Pałac Kultury i Nauki w Warszawie.";
                $place->address = "Plac Defilad 1, 00-901 Warszawa";
                $place->lat = "52.2317641";
                $place->lon = "21.005799675758887";
                $place->customer_id = $customer->id;
                $place->save();
                
                $status = Status::where("task_state", Status::TASK_STATE_OPEN)->first();
                
                $task = new Task;
                $task->project_id = $place->id;
                $task->status_id = $status->id;
                $task->priority = 1;
                $task->start_date = date("Y-m-d");
                $task->start_date_time = "12:00";
                $task->name = "Witaj w aplikacji ninjatask.pl!";
                $task->description = "<p><strong>Serdecznie dziękujemy za okazane zaufanie i rejestrację.</strong></p><p><br></p><p>Przed sobą widzisz przykładowe zadanie, które zostało przypisane do testowej lokalizacji oraz do Twojego nowo utworzonego konta. U góry po prawej stronie znajduje się przycisk \"Edytuj zadanie\" - za jego pomocą możesz zmienić opis zadania, ustawić planowaną datę realizacji czy przypisać zadanie do pozostałych pracowników. Pod nazwą zadania istnieje możliwość szybkiej zmiany priorytetu zadania lub statusu (w tym momencie zadanie posiada niski priorytet oraz ustawiony status \"Nowy\"). Poniżej odnajdziesz informacje na temat lokalizacji, przypisanych pracowników oraz przewidywana data lokalizacji. Przycisk \"Rozpocznij pracę\" uruchomi licznik czasu pracy oraz zmieni status zadania (możesz śmiało skorzystać z tego przycisku). Poniżej opisu zadania znajdują się kolejno: \"Zalogowany czas\" - informacje na temat poświęconego czasu pracy nad tym zadaniem, lista załączników przypisanych do zadania oraz sekcja dodanych komentarzy.</p><p><br></p><p>Cały czas rozwijamy aplikację i dbamy o to aby praca z nią była jak najbardziej intuicyjna i przyjazna. Jeśli będziesz miał/miała jakiekolwiek problemy lub pytania zapraszamy do kontaktu. Jesteśmy również otwarci na wszelki sugestie i pomysły.</p><p><br></p><p>Cieszymy się, że dołączyłeś do naszego grona oraz mamy nadzieję, że pozostaniesz z nami na dłużej.</p>";
                $task->created_user_id = Auth::user()->id;
                $task->save();
                
                $task->assignUsers([Auth::user()->id]);
                
                $taskTime = new TaskTime;
                $taskTime->task_id = $task->id;
                $taskTime->user_id = Auth::user()->id;
                $taskTime->status = "finished";
                $taskTime->started = time() - (45 * 60);
                $taskTime->finished = time();
                $taskTime->timer_started = null;
                $taskTime->total = 45 * 60;
                $taskTime->save();
            });
        }
    }
}
