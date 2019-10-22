# yaf_application
```$php
        // etcd 读取配置
        $etcd = new \Etcd\Client('127.0.0.1:2379', 'v3');
        // $res = $etcd->getAllKeys();
        $res = $etcd->getKeysWithPrefix("/micro-registry/go.micro.srv.stream/go.micro.srv.stream");

        $_json           = $res[array_rand($res)];
        $streamer_client = json_decode($_json, true);

        // 初始化client
        $opts   = [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ];
        $client = new Soa\Streamer\StreamerClient($streamer_client['nodes'][0]['address'], $opts);

        // 执行
        $cl      = $client->Stream();
        $request = new \Soa\Streamer\Request();
        for ($i = 0; $i < 10; $i++) {
            $cl->write($request->setCount($i));
            $response = $cl->read()->getCount();
            echo "STREAM: Sent msg $i got msg $response <br>";
        }
        $cl->writesDone();

        echo "========================<br>";

        $cl = $client->ServerStream($request->setCount(10));
        foreach ($cl->responses() as $v) {
            echo "ServerStream: got msg " . $v->getCount() . " <br>";
        }
```