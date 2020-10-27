<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel;

use Closure;
use Dog\Noticer\Contract\AbstractChannel;
use Dog\Noticer\Exception\NoticeException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\View\Engine\BladeEngine;
use Hyperf\View\Engine\PlatesEngine;
use Hyperf\View\Engine\SmartyEngine;
use Hyperf\View\Engine\ThinkEngine;
use Hyperf\View\Engine\TwigEngine;
use Psr\Container\ContainerInterface;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;
use Swift_Signers_SMimeSigner;
use Swift_SmtpTransport;
use Throwable;

class Email extends AbstractChannel
{
    /**
     * 错误码定义.
     */
    // 未发生错误
    const ERR_NONE = 0;

    // 状态码非200
    const ERR_NOT_200 = 5001;

    // 达到频率限制
    const ERR_REACH_LIMIT = 5003;

    // 其他错误
    const ERR_OTHER = 5004;

    /**
     * 文本类型.
     */
    const CONTENT_HTML = 'text/html';

    const CONTENT_PLAIN = 'text/plain';

    /**
     * 优先级.
     */
    const PRIORITY_HIGHEST = 1;

    const PRIORITY_HIGH = 2;

    const PRIORITY_NORMAL = 3;

    const PRIORITY_LOW = 4;

    const PRIORITY_LOWEST = 5;

    /**
     * 常见图片Mime.
     */
    const MIME_JPEG = 'image/jpeg';

    const MIME_PNG = 'image/png';

    const MIME_GIF = 'image/gif';

    const MIME_BMP = 'application/x-bmp';

    /**
     * @var Swift_Message
     */
    protected $message;

    /**
     * 默认的transport.
     *
     * @var Swift_SmtpTransport
     */
    protected $transport;

    /**
     * 以行设置文本的内容.
     *
     * @var array
     */
    protected $lines = [];

    /**
     * 注册组件列表.
     *
     * @var array
     */
    protected $plugins = [];

    /**
     * 发送失败的帐号.
     *
     * @var null|array
     */
    protected $fails;

    /**
     * 通知渠道名称.
     *
     * @var string
     */
    protected $name = 'email';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->transport = $this->createSmtpTransport();
        $this->init();
    }

    /**
     * 重置实例.
     * @return self
     */
    public function init()
    {
        $this->message = new Swift_Message();
        $this->message->setFrom($this->config['from_address'], $this->config['from_name']);

        $this->lines = [];
        $this->fails = [];

        return $this;
    }

    /**
     * 设置邮件主题.
     *
     * @param string $subject
     * @return self
     */
    public function subject($subject)
    {
        $this->message->setSubject($subject);

        return $this;
    }

    /**
     * 设置发件人.
     *
     * @param array|string $address
     * @param string $name
     * @return self
     */
    public function from($address, $name = null)
    {
        $this->message->setFrom($address, $name);

        return $this;
    }

    /**
     * 设置收件人.
     *
     * @param array|string $address
     * @param string $name
     * @return self
     */
    public function to($address, $name = null)
    {
        $this->message->setTo($address, $name);

        return $this;
    }

    /**
     * 设置邮件内容为HTML内容.
     *
     * @param string $content
     * @return self
     */
    public function html($content)
    {
        $this->message->setBody($content, static::CONTENT_HTML);

        return $this;
    }

    /**
     * 设置邮件内容为纯文本内容.
     *
     * @param string $content
     * @return self
     */
    public function text($content)
    {
        $this->message->setBody($content, static::CONTENT_PLAIN);

        return $this;
    }

    /**
     * 设置邮件内容为纯文本内容.
     *
     * @param string $content
     * @return self
     */
    public function raw($content)
    {
        return $this->text($content);
    }

    /**
     * 添加一行数据.
     *
     * @param string $content
     * @return self
     */
    public function line($content = '')
    {
        $this->lines[] = $content;

        return $this;
    }

    /**
     * 载入一个模板作为邮件内容.
     *
     * @param string $template 模板名称
     * @param array $param 模板变量参数
     * @return self
     */
    public function view($template, $param = [])
    {
        $engine = $this->container->get(ConfigInterface::class)->get('view.engine', BladeEngine::class);

        return $this->sendByView($engine, $template, $param);
    }

    /**
     * 载入一个blade模板作为邮件内容.
     *
     * @param string $template 模板名称
     * @param array $param 模板变量参数
     * @return self
     */
    public function blade($template, $param = [])
    {
        return $this->sendByView(BladeEngine::class, $template, $param);
    }

    /**
     * 载入一个smarty模板作为邮件内容.
     *
     * @param string $template 模板名称
     * @param array $param 模板变量参数
     * @return self
     */
    public function smarty($template, $param = [])
    {
        return $this->sendByView(SmartyEngine::class, $template, $param);
    }

    /**
     * 载入一个twig模板作为邮件内容.
     *
     * @param string $template 模板名称
     * @param array $param 模板变量参数
     * @return self
     */
    public function twig($template, $param = [])
    {
        return $this->sendByView(TwigEngine::class, $template, $param);
    }

    /**
     * 载入一个plates模板作为邮件内容.
     *
     * @param string $template 模板名称
     * @param array $param 模板变量参数
     * @return self
     */
    public function plates($template, $param = [])
    {
        return $this->sendByView(PlatesEngine::class, $template, $param);
    }

    /**
     * 载入一个ThinkTemplate模板作为邮件内容.
     *
     * @param string $template 模板名称
     * @param array $param 模板变量参数
     * @return self
     */
    public function thinkTemplate($template, $param = [])
    {
        return $this->sendByView(ThinkEngine::class, $template, $param);
    }

    /**
     * 载入一个模板引擎作为邮件内容.
     *
     * @param string $template 模板名称
     * @param array $param 模板变量参数
     * @param mixed $engine
     * @return self
     */
    public function sendByView($engine, $template, $param = [])
    {
        $config = $this->container->get(ConfigInterface::class)->get('view.config', []);

        $content = $this->container->get($engine)->render($template, $param, $config);

        return $this->html($content);
    }

    /**
     * 添加附件.
     *
     * @param string $filePath
     * @param null|\Closure|string $attr
     * @return self
     */
    public function attach($filePath, $attr = null)
    {
        $attachment = Swift_Attachment::fromPath($filePath);
        if ($attr instanceof Closure) {
            call_user_func_array($attr, [&$attachment, $this]);
        } elseif ($attr) {
            $attachment->setFilename($this->cnEncode($attr));
        } else {
            // 修复中文文件名乱码bug
            $tmp = str_replace('\\', '/', $filePath);
            $tmp = explode('/', $tmp);
            $filename = end($tmp);
            $attachment->setFilename($this->cnEncode($filename));
        }
        $this->message->attach($attachment);

        return $this;
    }

    /**
     * Signed/Encrypted Message.
     *
     * @param Swift_Signers_SMimeSigner $smimeSigner
     * @return self
     */
    public function signCertificate($smimeSigner)
    {
        if ($smimeSigner instanceof \Closure) {
            $signer = new Swift_Signers_SMimeSigner();
            call_user_func_array($smimeSigner, [&$signer]);
            $this->message->attachSigner($signer);
        }

        return $this;
    }

    /**
     * 设置字符编码
     *
     * @param string $charset
     * @return self
     */
    public function charset($charset)
    {
        $this->message->setCharset($charset);

        return $this;
    }

    /**
     * 设置邮件最大长度.
     *
     * @param int $length
     * @return self
     */
    public function lineLength($length)
    {
        $this->message->setMaxLineLength($length);

        return $this;
    }

    /**
     * 设置优先级.
     *
     * @param int $priority
     * @return self
     */
    public function priority($priority)
    {
        $this->message->setPriority($priority);

        return $this;
    }

    /**
     * Requesting a Read Receipt.
     *
     * @param string $address
     * @return self
     */
    public function readReceiptTo($address)
    {
        $this->message->setReadReceiptTo($address);

        return $this;
    }

    /**
     * 注册SwiftMailer插件
     * 详情请见 http://swiftmailer.org/docs/plugins.html.
     *
     * @param object $plugin
     */
    public function registerPlugin($plugin)
    {
        $this->plugins[] = $plugin;
    }

    /**
     * 获取头信息.
     *
     * @return \Swift_Mime_HeaderSet
     */
    public function getHeaders()
    {
        return $this->message->getHeaders();
    }

    /**
     * 获取头信息 (字符串).
     *
     * @return string
     */
    public function getHeadersString()
    {
        return $this->getHeaders()->toString();
    }

    /**
     * 发送邮件.
     *
     * @param null|\Closure message
     * @param null|\Closure|string $transport
     * @param null|\Closure $send
     * @param null|mixed $message
     * @throws NoticeException
     * @return bool|int
     */
    public function send($message = null, $transport = null, $send = null)
    {
        try {
            // 获取将行数据设置到message里
            if ($this->lines) {
                $this->message->setBody(implode("\r\n", $this->lines), static::CONTENT_PLAIN);
                $this->lines = [];
            }
            // 匿名函数
            if ($message instanceof \Closure) {
                call_user_func_array($message, [&$this, &$this->message]);
            }
            // 直接传递的是Swift_Transport对象
            if (is_object($transport)) {
                $transportDriver = $transport;
            } else {
                $transportDriver = $this->transport;
            }

            $swiftMailer = new Swift_Mailer($transportDriver);

            // 注册插件
            if ($this->plugins) {
                foreach ($this->plugins as $plugin) {
                    $swiftMailer->registerPlugin($plugin);
                }
                $this->plugins = [];
            }

            // 发送邮件
            if ($send instanceof \Closure) {
                call_user_func_array($send, [$swiftMailer, $this]);
            } else {
                return $swiftMailer->send($this->message, $this->fails);
            }
        } catch (Throwable $e) {
            throw new NoticeException('notice by email failed, error: ' . $e->getMessage(), static::ERR_OTHER, [
                'headersString' => $this->getHeadersString(),
            ], $e);
        }
    }

    /**
     * 获取发送错误的邮箱帐号列表.
     *
     * @return mixed
     */
    public function getFails()
    {
        return $this->fails;
    }

    /**
     * 中文文件名编码, 防止乱码
     *
     * @param string $string
     *
     * @return string
     */
    public function cnEncode($string)
    {
        return '=?UTF-8?B?' . base64_encode($string) . '?=';
    }

    /**
     * 创建一个smtp传输对象
     *
     * @return Swift_SmtpTransport
     */
    protected function createSmtpTransport()
    {
        $transport = new Swift_SmtpTransport(
            $this->config['host'],
            $this->config['port'],
            $this->config['encryption']
        );

        $transport->setUsername($this->config['username']);
        $transport->setPassword($this->config['password']);

        return $transport;
    }
}
