<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\PlayerPosition;
use App\Enums\PlayerSkill;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;
use Validator;

use App\Models\Player;
use App\Models\PlayerSkill as Skill;

class TeamController extends Controller
{
    public function selection(Request $request) {

        $messages = [
            '*.position' => 'Invalid value for position: :input',
            '*.mainSkill' => 'Invalid value for mainSkill: :input',
            '*.mainSkill.distinct' => 'The request should allow the same position and skill combination only once.',
            '*.numberOfPlayers' => 'Invalid value for numberOfPlayers: :input',
        ];

        $rules = [
            '*.position' => new Enum(PlayerPosition::class),
            '*.mainSkill' => [new Enum(PlayerSkill::class), Rule::when('*.position:input', 'distinct')],
            '*.numberOfPlayers' => 'Integer',
        ];

        $validator = Validator::make(
            $request->all(),
            $rules,
            $messages
        );

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first()], 400);
        }

        // selecting ids
        $ids = [];
        foreach ($request->all() as $requirement) {
            $limit = $requirement['numberOfPlayers'];
            $players = Player::whereNotIn('id', $ids)->where('position', $requirement['position'])->get();
            $playerCount = $players->count();

            if ($playerCount < $limit) {
                return response()->json(['message' => 'Insufficient number of players for position: '. $requirement["position"]], 400);
            }

            $playerIds = $players->pluck('id');
            $actualSkills = Skill::whereIn('player_id', $playerIds)->orderBy('value')->where('skill', $requirement['mainSkill']);
            $actualCount = $actualSkills->count();

            if ($actualCount < $limit) {
                $more = $limit - $actualCount;
                $ids = array_merge($ids, $actualSkills->pluck('player_id')->all());
                $additional = Skill::whereIn('player_id', $playerIds)->orderBy('value')->where('skill', '!=',$requirement['mainSkill'])->skip(0)->take($more)->get();
                $ids = array_merge($ids, $additional->pluck('player_id')->all());

            } else {
                $result = $actualSkills->skip(0)->take($limit)->get();
                $ids = array_merge($ids, $result->pluck('player_id')->all());
            }
        }

        // producing result
        $response = [];
        $players = Player::whereIn('id', $ids)->get();
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
}
