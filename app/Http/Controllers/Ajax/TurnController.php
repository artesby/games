<?php
/**
 *
 * @author IrenJones
 */
namespace App\Http\Controllers\Ajax;

use App\Http\Requests\TurnRequest;
use Illuminate\Http\Request;
use App\Game;
use App\Http\Controllers\BaseController;
use Auth;

class TurnController extends BaseController
{
    /**
     * Make turn or not
     *
     * @return Response
     */
    public function turn(Request $msg)
    {
        if ($msg->ajax()){
            return response()->json(['this is' => 'ajaxa']);
        }
        
        $data = $msg; //json_decode($msg->getContent(), true);
        $user=  Auth::user();
        
        $game = Game::find($data['game']);
        
        $turnValidatedSuccessfully = $game->turn(
            $user,
            $data['figure'],
            $data['x'],
            $data['y'],
            $data['typeId']
        );

        if ($turnValidatedSuccessfully === true) {
            $answer = json_encode([
                'name' => 'turn',
                'data' =>
                [
                    'state' => 'success'
                ]
            ]);
        } else {
            $answer = json_encode([
                'name' => 'turn',
                'data' =>
                [
                    'state' => 'failed'
                ]
            ]);
        }
        return response()->json($answer);
    }
}
