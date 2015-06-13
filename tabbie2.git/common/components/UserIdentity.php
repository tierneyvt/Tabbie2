<?php

namespace common\components;

use common\models\Adjudicator;
use common\models\Feedback;
use common\models\LanguageOfficer;
use common\models\Panel;
use common\models\Team;
use \common\models\Tournament;
use \common\models\User;
use common\models\Debate;
use yii\base\Exception;
use Yii;

class UserIdentity extends \yii\web\User {

	public static function className() {
		return "common\components\UserIdentity";
	}

	/**
	 * Check if user is the tabmaster of the torunament
	 *
	 * @param int $tournament_id
	 *
	 * @return boolean
	 */
	public function isTabMaster($tournament) {
		if ($tournament instanceof Tournament && $tournament->tabmaster_user_id == $this->id) {
			\Yii::trace("User is Tab Master for Tournament #" . $tournament->id, __METHOD__);
			return true;
		}
		else if (\Yii::$app->user->isAdmin()) //Admin secure override
			return true;
		else
			return false;
	}

	/**
	 * Check if user is the convenor of the torunament
	 *
	 * @param int $tournament_id
	 *
	 * @return boolean
	 */
	public function isConvenor($tournament) {
		if ($tournament instanceof Tournament && $tournament->convenor_user_id == $this->id) {
			\Yii::trace("User is Convenor for Tournament #" . $tournament->id, __METHOD__);
			return true;
		}
		else if (\Yii::$app->user->isAdmin()) //Admin secure override
			return true;
		return false;
	}

	/**
	 * Check if the user is Admin
	 *
	 * @return boolean
	 */
	public function isAdmin() {
		$user = $this->getModel();
		if ($user instanceof User && $user->role == User::ROLE_ADMIN) {
			return true;
		}

		return false;
	}

	public function isLanguageOfficer($tournament) {
		if ($tournament instanceof Tournament) {
			if ($tournament->status != Tournament::STATUS_CLOSED) {
				if (LanguageOfficer::find()->where([
						"tournament_id" => $tournament->id,
						"user_id" => $this->id,
					])->count() == 1
				) {
					\Yii::trace("User is LanguageOfficer for Tournament #" . $tournament->id, __METHOD__);
					return true;
				}
				else if (\Yii::$app->user->isAdmin()) //Admin secure override
					return true;
			}
		}
		else throw new Exception("Wrong Parameter not a valid tournament");

		return false;
	}

	/**
	 * @param Tournament $tournament
	 *
	 * @return bool
	 */
	public function isRegistered($tournament) {

		if ($this->isAdmin() || $this->isConvenor($tournament) || $this->isLanguageOfficer($tournament) || $this->isTabMaster($tournament))
			return true;

		if ($this->isTeam($tournament) || $this->isAdjudicator($tournament))
			return true;

		return false;

	}

	public function isTeam($tournament) {
		if ($this->isTeamA($tournament) || $this->isTeamB($tournament))
			return true;
		return false;
	}

	public function isTeamA($tournament) {
		//check if Team
		$team = Team::find()->tournament($tournament->id)
		            ->andWhere(["speakerA_id" => Yii::$app->user->id])
		            ->count();
		if ($team > 0)
			return true;

		return false;
	}

	public function isTeamB($tournament) {
		//check if Team
		$team = Team::find()->tournament($tournament->id)
		            ->andWhere(["speakerB_id" => Yii::$app->user->id])
		            ->count();
		if ($team > 0)
			return true;

		return false;
	}

	public function isAdjudicator($tournament) {
		//check if Adjudicator
		$adju = Adjudicator::find()->tournament($tournament->id)
		                   ->andWhere(["user_id" => Yii::$app->user->id])
		                   ->count();
		if ($adju > 0)
			return true;

		return false;
	}

	/**
	 * @param Round $lastRound
	 *
	 * @return boolean
	 */
	public function hasChairedLastRound($info) {
		if ($info['type'] == 'judge' && $info['pos'] == 1) {
			return true;
		}
		return false;
	}

	/**
	 * @param Round $lastRound
	 *
	 * @return Debate
	 */
	public function hasOpenFeedback($info) {

		$debate = $info['debate'];
		if ($debate && $this->id > 0) {
			/** check teams* */
			if ($debate->og_feedback == 0 && $debate->isOGTeamMember($this->id))
				return ["type" => Feedback::FROM_TEAM, "id" => $debate->id, "ref" => $debate->og_team_id];
			if ($debate->oo_feedback == 0 && $debate->isOOTeamMember($this->id))
				return ["type" => Feedback::FROM_TEAM, "id" => $debate->id, "ref" => $debate->oo_team_id];
			if ($debate->cg_feedback == 0 && $debate->isCGTeamMember($this->id))
				return ["type" => Feedback::FROM_TEAM, "id" => $debate->id, "ref" => $debate->cg_team_id];
			if ($debate->co_feedback == 0 && $debate->isCOTeamMember($this->id))
				return ["type" => Feedback::FROM_TEAM, "id" => $debate->id, "ref" => $debate->co_team_id];

			/** check judges * */
			foreach ($debate->panel->adjudicatorInPanels as $judge) {
				if ($judge->got_feedback == 0 && $judge->adjudicator->user_id == $this->id) {
					if ($judge->function == Panel::FUNCTION_CHAIR)
						$type = Feedback::FROM_CHAIR;
					else
						$type = Feedback::FROM_WING;

					return ["type" => $type, "id" => $debate->id, "ref" => $judge->adjudicator->id];
				}
			}
		}
		return false;
	}

	/**
	 * Get the full User Model
	 *
	 * @return \common\models\User
	 */
	public function getModel() {
		return $user = User::findOne($this->id);
	}

	public function getRoleModel($tid) {
		$adj = \common\models\Adjudicator::find()->where(["tournament_id" => $tid, "user_id" => $this->id])->one();
		if ($adj instanceof \common\models\Adjudicator)
			return $adj;
		else {
			$team = \common\models\Team::find()
			                           ->where("tournament_id = :tid AND (speakerA_id = :uid OR speakerB_id = :uid)", [
				                           ":tid" => $tid,
				                           ":uid" => $this->id,
			                           ])
			                           ->one();
			if ($team instanceof \common\models\Team)
				return $team;
		}
		return null;
	}

}
