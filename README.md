
#  StopPropaganda

Russia has invaded Ukraine and starts a war. Russian media denies any of its attacks on civilian as well as denies any Russian troops casualties. According to them, they are doing this "special operation" to protect Ukrainians.

Mykhailo Federov (Vice Prime Minister and Minister of Digital Transformation of Ukraine) has shared this [tweet](https://twitter.com/FedorovMykhailo/status/1497642156076511233) post encouraging cyberattack on certain targets via Telegram group. This will be primary source of the target websites for this application.

Anyone who knows someone from Russia should send them a personal message with a link to Zelenskyy's speech to the Russian people. It is important that as many as possible in Russia hear his words. Write to all Russians you know!  #Ukraine 🇺🇦 🇷🇺 https://youtu.be/Fwzb_JX7u04

INFO: If you do not know anything about PHP, you maybe want to check out this HTML/JS code: https://github.com/ajax-lives/NoRussian/

DISCLAIMER: (D)DOS'ing is illegal! Usage of this tool is intended for educational purposes only.

---

### prepare
```shell
sudo apt install wget php-cli php-zip php-curl unzip
cd ~
wget -qO composer-setup.php https://getcomposer.org/installer  
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
```

### install
```shell
cd ~
git clone https://github.com/voku/stoppropaganda
cd stoppropaganda
/usr/local/bin/composer install
```

### use
e.g.:
```shell
cd ~/stoppropaganda/
while :; do echo '... start again ...'; timeout 1800 php run.php; done;
```
