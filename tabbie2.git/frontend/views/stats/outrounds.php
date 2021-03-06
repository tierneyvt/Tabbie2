<?php
/**
 * outrounds.php File
 * @package  Tabbie2
 * @author   jareiter
 * @version
 */
use kartik\helpers\Html;

?>
<div class="tab-outrounds-container">

    <?
    $rounds = $model->getRounds()
            ->andWhere("type > " . \common\models\Round::TYP_IN)
            ->orderBy(["level" => SORT_ASC, "type" => SORT_ASC])
            ->all();
    ?>
    <?
    /** @var \common\models\Round $r */
    if (isset($rounds[0])) {
        $old = $rounds[0]->level;
    } else {
        $old = 0;
    }
    foreach ($rounds as $r):
        if ($r->level != $old) {
            echo "<hr>";
            $old = $r->level;
        }
        ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><?= $r->name ?></h3>
            </div>
            <table class="table">
                <? foreach (\common\models\Team::getPos() as $i => $p): ?>
                    <th width="25%">
                        <?= \common\models\Team::getPosLabel($i) ?>
                    </th>
                <? endforeach; ?>
                <?
                /** @var \common\models\Debate $d */
                foreach ($r->debates as $d): ?>
                    <tr>
                        <? foreach (\common\models\Team::getPos() as $i => $p): ?>
                            <td>
                                <? if (isset($d->result) && $d->result->{$p . "_place"} > (($r->level != 1) ? 2 : 1)) {
                                    echo Html::tag("s", $d->{$p . "_team"}->name);
                                } else {
                                    echo Html::tag("span", $d->{$p . "_team"}->name);
                                }
                                ?>
                            </td>
                        <? endforeach; ?>
                    </tr>
                <? endforeach; ?>
            </table>
        </div>
    <? endforeach; ?>
</div>