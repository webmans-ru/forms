<?php

namespace app\modules\forms\models\events;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\forms\models\events\EFCity;

/**
 * EFCitySearch represents the model behind the search form of `app\modules\forms\models\events\EFCity`.
 */
class EFCitySearch extends EFCity
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['iid'], 'integer'],
            [['cname'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = EFCity::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'iid' => $this->iid,
        ]);

        $query->andFilterWhere(['like', 'cname', $this->cname]);

        return $dataProvider;
    }
}
