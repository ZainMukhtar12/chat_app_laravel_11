<?php

namespace App\Livewire;

use App\Events\MessageSendEvent;
use App\Models\Message;
use App\Models\User;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Broadcast;
use Livewire\Component;

class ChatComponent extends Component
{
    public $user;
    public $sender_id;
    public $receiver_id;
    public $message;
    public $messages = [];

    public function mount($user_id)
    {
        $this->sender_id = auth()->user()->id;
        $this->receiver_id = $user_id;

        $messages = Message::where(function($query) {
            $query->where('sender_id', $this->sender_id)
                  ->where('receiver_id', $this->receiver_id);
        })->orWhere(function($query) {
            $query->where('sender_id', $this->receiver_id)
                  ->where('receiver_id', $this->sender_id);
        })->with('sender:id,name', 'receiver:id,name')->get();
        foreach ($messages as $message) {
            $this->appenChatMessage($message);
        }
       // dd($messages->toArray());

        $this->user = User::whereId($user_id)->first();
    }
    #[on('echo-private:chat-channel.{sender_id},MessageSendEvent')]
    public function listenForMessage($event){
        $chatMessage = Message::whereId($event['message']['id'])
        ->with('sender:id,name', 'receiver:id,name')
        ->first();
        $this->appenChatMessage($chatMessage);
    }
    public function appenChatMessage($message){
        $this->messages[] = [
            'id'  => $message->id,
            'message'  => $message->message,
            'sender'  => $message->sender->name,
            'receiver'  => $message->receiver->name,
        ];
    }
    public function sendMessage(){
       $chatMessage = new Message();
       $chatMessage->sender_id = $this->sender_id;
       $chatMessage->receiver_id = $this->receiver_id;
       $chatMessage->message = $this->message;
       $chatMessage->save();
       $this->appenChatMessage($chatMessage);
       Broadcast(new MessageSendEvent($chatMessage))->toOthers();
       $this->message = '';

    }
    public function render()
    {
        return view('livewire.chat-component');
    }

}
