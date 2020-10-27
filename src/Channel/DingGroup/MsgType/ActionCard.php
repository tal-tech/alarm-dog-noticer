<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\DingGroup\MsgType;

use Dog\Noticer\Channel\DingGroup\MsgType;

class ActionCard extends MsgType
{
    /**
     * @var string
     */
    protected $type = 'actionCard';

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
     * 是否隐藏发送者头像.
     *
     * @var bool
     */
    protected $hiddenAvatar = false;

    /**
     * ActionCard constructor.
     *
     * @param string $title
     * @param string $markdown markdown格式内容
     */
    public function __construct($title, $markdown)
    {
        $this->data = [
            'title' => $title,
            'text' => $markdown,
        ];
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
            'singleTitle' => $title,
            'singleURL' => $url,
        ];
    }

    /**
     * 独立跳转ActionCard.
     *
     * @param array $btns [['title' => '{title}', 'actionURL' => '{actionURL}']]
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
            'actionURL' => $url,
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
     * 是否隐藏发送者头像.
     *
     * @param bool $hidden
     * @return self
     */
    public function setHiddenAvatar($hidden = true)
    {
        $this->hiddenAvatar = $hidden;

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
            $data['btns'] = $this->btnsConfig;
        } elseif ($this->mode == 'single') {
            $data = array_merge($data, $this->singleBtnConfig);
        }
        $data['hideAvatar'] = $this->hiddenAvatar ? '1' : '0';
        $data['btnOrientation'] = $this->btnsIsVertical ? '0' : '1';

        return [
            'msgtype' => $this->getType(),
            $this->getType() => $data,
        ];
    }
}
