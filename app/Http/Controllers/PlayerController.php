<?php

// /////////////////////////////////////////////////////////////////////////////
// PLEASE DO NOT RENAME OR REMOVE ANY OF THE CODE BELOW.
// YOU CAN ADD YOUR CODE TO THIS FILE TO EXTEND THE FEATURES TO USE THEM IN YOUR WORK.
// /////////////////////////////////////////////////////////////////////////////

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Enums\PlayerPosition;
use App\Enums\PlayerSkill;
use Illuminate\Validation\Rules\Enum;
use Validator;
use App\Models\Player;

class PlayerController extends Controller
{
    public function index()
    {
        $players = Player::all();
        $response = [];
        if (!empty($players)) {
            foreach ($players as $key => $player) {
                $response[$key]['id'] = $player->id;
                $response[$key]['name'] = $player->name;
                $response[$key]['position'] = $player->position;

                if (!empty($player->skills)) {
                    foreach ($player->skills as $idx => $skill) {
                        $response[$key]['playerSkills'][$idx]['id'] = $skill->id;
                        $response[$key]['playerSkills'][$idx]['skill'] = $skill->skill;
                        $response[$key]['playerSkills'][$idx]['value'] = $skill->value;
                        $response[$key]['playerSkills'][$idx]['playerId'] = $skill->player_id;
                    }
                }
            }
        }

        if (!empty($response)) {
            return response()->json($response);
        }
        return response("Failed", 500);
    }

    public function show()
    {
        return response("Failed", 500);
    }

    public function store(Request $request)
    {
        $messages = [
            'name' => 'Invalid value for name: :input',
            'position' => "Invalid value for position: :input",
            'playerSkills.*.skill' => "Invalid value for playerSkill: :input",
            'playerSkills.*.skill.distinct' => "Invalid value for playerSkill: :input cannot be reused",
            'playerSkills.*.value' => "Invalid value for playerSkill value: :input",
        ];

        $rules = [
            'name' => 'Required',
            'position' => new Enum(PlayerPosition::class),
            'playerSkills.*.skill' => [new Enum(PlayerSkill::class), 'distinct'],
            'playerSkills.*.value' => 'Integer',
        ];

        $validator = Validator::make(
            $request->all(),
            $rules,
            $messages
        );

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first()], 400);
        }

        $player = new Player();
        $player->name = $request->name;
        $player->position = $request->position;
        $player->save();
        if (!empty($request->playerSkills)) {
            foreach ($request->playerSkills as $playerSkill) {
                $player->skills()->create([
                    'skill' => $playerSkill['skill'],
                    'value' => $playerSkill['value'],
                ]);
            }
        }
        $response = [];
        $playerData = Player::find($player->id);
        if (!empty($playerData)) {
            $response['id'] = $playerData->id;
            $response['name'] = $playerData->name;
            $response['position'] = $playerData->position;
        }
        if (!empty($playerData->skills)) {
            foreach ($playerData->skills as $key => $skill) {
                $response['playerSkills'][$key]['id'] = $skill->id;
                $response['playerSkills'][$key]['skill'] = $skill->skill;
                $response['playerSkills'][$key]['value'] = $skill->value;
                $response['playerSkills'][$key]['playerId'] = $skill->player_id;
            }
        }
        if (!empty($response)) {
            return response()->json($response);
        }
        return response("Failed", 500);
    }

    public function update(Request $request, $id)
    {
        $messages = [
            'name' => 'Invalid value for name: :input',
            'position' => "Invalid value for position: :input",
            'playerSkills.*.skill' => "Invalid value for playerSkill: :input",
            'playerSkills.*.skill.distinct' => "Invalid value for playerSkill: :input cannot be reused",
            'playerSkills.*.value' => "Invalid value for playerSkill value: :input",
        ];

        $rules = [
            'name' => 'String',
            'position' => new Enum(PlayerPosition::class),
            'playerSkills.*.skill' => [new Enum(PlayerSkill::class), 'distinct'],
            'playerSkills.*.value' => 'Integer',
        ];

        $validator = Validator::make(
            $request->all(),
            $rules,
            $messages
        );

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first()], 400);
        }

        $player = Player::find($id);
        $player->name = $request->input('name');
        $player->position = $request->input('position');
        $player->save();
        if (!empty($request->playerSkills)) {
            $player->skills()->delete();
            foreach ($request->playerSkills as $playerSkill) {
                $player->skills()->updateOrCreate([
                    'skill' => $playerSkill['skill'],
                    'value' => $playerSkill['value'],
                ])->save();
            }
        }

        $response = [];
        if (!empty($player)) {
            $response['id'] = $player->id;
            $response['name'] = $player->name;
            $response['position'] = $player->position;
        }
        if (!empty($player->skills)) {
            foreach ($player->skills as $key => $skill) {
                $response['playerSkills'][$key]['id'] = $skill->id;
                $response['playerSkills'][$key]['skill'] = $skill->skill;
                $response['playerSkills'][$key]['value'] = $skill->value;
                $response['playerSkills'][$key]['playerId'] = $skill->player_id;
            }
        }
        if (!empty($response)) {
            return response()->json($response);
        }
        return response("Failed", 500);
    }

    public function destroy(Request $request, $id)
    {
        $player = Player::findOrfail($id);
        $token = "Bearer SkFabTZibXE1aE14ckpQUUxHc2dnQ2RzdlFRTTM2NFE2cGI4d3RQNjZmdEFITmdBQkE=";
        if ($request->header('Authorization') === $token) {
            if($player->delete()) {
                return response("Player is deleted", 200);
            }
            return response("Failed", 500);
        }
        return response("Failed", 500);
    }
}
