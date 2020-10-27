<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\YachGroup\MsgType;

use Dog\Noticer\Channel\YachGroup\MsgType;

class ActionCard extends MsgType
{
    /**
     * @var string
     */
    protected $type = 'action_card';

    /**
     * ActionCard的模式.
     *
     * @var string single or btns
     */
    protected $mode;

    /**
     * 整体跳转配置.
     *
     * @var array
     */
    protected $singleBtnConfig = [];

    /**
     * 独立跳转配置.
     *
     * @var array
     */
    protected $btnsConfig = [];

    /**
     * btn是否竖直排列.
     *
     * @var bool
     */
    protected $btnsIsVertical = false;

    /**
     * ActionCard constructor.
     *
     * @param string $title
     * @param string $markdown markdown格式内容
     * @param string $image 图片url
     * @param string $contentTitle 内容体title
     */
    public function __construct($title, $markdown, $image = '', $contentTitle = '')
    {
        $this->data = [
            'title' => $title,
            'markdown' => $markdown,
        ];
        if ($image) {
            $this->data['image'] = $image;
        }
        if ($contentTitle) {
            $this->data['content_title'] = $contentTitle;
        }
    }

    /**
     * 整体跳转ActionCard.
     *
     * @param string $title
     * @param string $url
     */
    public function single($title, $url)
    {
        $this->mode = 'single';

        $this->singleBtnConfig = [
            'single_title' => $title,
            'single_url' => $url,
        ];
    }

    /**
     * 独立跳转ActionCard.
     *
     * @param array $btns [['title' => '{title}', 'action_url' => '{action_url}']]
     */
    public function btns(array $btns)
    {
        $this->mode = 'btns';

        $this->btnsConfig = $btns;
    }

    /**
     * 独立跳转ActionCard.
     *
     * @param string $title
     * @param string $url
     * @param bool $append
     * @return self
     */
    public function btn($title, $url, $append = true)
    {
        $this->mode = 'btns';

        $btn = [
            'title' => $title,
            'action_url' => $url,
        ];

        if ($append) {
            $this->btnsConfig[] = $btn;
        } else {
            $this->btnsConfig = [$btn];
        }

        return $this;
    }

    /**
     * 设置btn是否垂直排列.
     *
     * @param bool $isVertical
     * @return self
     */
    public function setVertical($isVertical = true)
    {
        $this->btnsIsVertical = $isVertical;

        return $this;
    }

    /**
     * @return array
     */
    public function getParam()
    {
        $data = $this->toArray();

        if (is_null($this->mode)) {
            throw new \InvalidArgumentException('请设置ActionCard的模式');
        }

        if ($this->mode == 'btns') {
            $data['btn_json_list'] = $this->btnsConfig;
            $data['btn_orientation'] = $this->btnsIsVertical ? '0' : '1';
        } elseif ($this->mode == 'single') {
            $data = array_merge($data, $this->singleBtnConfig);
        }

        return [
            'msgtype' => $this->getType(),
            $this->getType() => $data,
        ];
    }
}
