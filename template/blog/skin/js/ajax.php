//相关的sql语然,按照你自己的来,根据获取的id,update新"赞"那个字段,每点一次就+1
//然后再获取"赞"字段的最新值
$data=array(
        "html"=>23 //这里是获取到的赞的最新值
    );
}
echo json_encode($data);