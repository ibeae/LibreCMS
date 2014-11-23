<?php
$rank=0;
$show='categories';
if(isset($args[0])&&$args[0]=='add'){
	$ti=time();
	if($view=="bookings"){
		$q=$db->prepare("INSERT INTO content (contentType,status,ti,tis) VALUES ('booking','unconfirmed',:ti,:tis)");
		$q->execute(array(':ti'=>$ti,':tis'=>$ti));
	}else{
		$schema='';
		$comments=0;
		if($view=='article'){$schema='blogPost';}
		if($view=='inventory'){$schema='Product';}
		if($view=='service'){$schema='Service';}
		if($view=='gallery'){$schema='ImageGallery';}
		if($view=='testimonials'){$schema='Review';}
		if($view=='news'){$schema='NewsArticle';}
		if($view=='events'){$schema='Event';}
		if($view=='portfolio'){$schema='CreativeWork';}
		if($view=='proofs'){$schema='CreativeWork';$comments=1;}
		$q=$db->prepare("INSERT INTO content (options,uid,contentType,schemaType,status,active,ti) VALUES ('00000000',:uid,:contentType,:schemaType,'unpublished','1',:ti)");
		if(isset($user['id']))$uid=$user['id'];else $uid=0;
		$q->execute(array(':contentType'=>$view,':uid'=>$uid,':schemaType'=>$schema,':ti'=>$ti));
	}
	$id=$db->lastInsertId();
	$args[0]=ucfirst($view).' '.$id;
	$q=$db->prepare("UPDATE content SET title=:title WHERE id=:id");
	$q->execute(array(':title'=>$args[0],':id'=>$id));
	if($view!='bookings')$show='item';
	$rank=0;
}
if($view=='index'){
	preg_match('/<settings itemCount=([\w\W]*?) contentType=([\w\W]*?)>/',$html,$matches);
	$itemCount=$matches[1];
	if($itemCount==0)$itemCount=10;
	$contentType=$matches[2];
	if($contentType=='all')$contentType='%';
	$s=$db->prepare("SELECT * FROM content WHERE contentType LIKE :contentType AND contentType NOT LIKE 'message%' AND contentType!='testimonials' AND contentType!='proofs' AND status LIKE :status ORDER BY ti DESC LIMIT $itemCount");
	$s->execute(array(':contentType'=>$contentType,':status'=>$status));
}elseif($view=='search'){
	$search='%';
	if(isset($args[0]))$search='%'.str_replace('-',' ',$args[0]).'%';else $search='%'.str_replace('-',' ',filter_input(INPUT_POST,'search',FILTER_SANITIZE_STRING)).'%';
	$s=$db->prepare("SELECT * FROM content WHERE code=:code OR LOWER(brand) LIKE LOWER(:brand) OR LOWER(title) LIKE LOWER(:title) OR LOWER(category_1) LIKE LOWER(:category_1) OR LOWER(category_2) LIKE LOWER(:category_2) OR LOWER(keywords) LIKE LOWER(:keywords) OR LOWER(tags) LIKE LOWER(:tags) OR LOWER(caption) LIKE LOWER(:caption) OR LOWER(notes) LIKE LOWER(:notes) AND contentType NOT LIKE 'message%' ORDER BY ti DESC");
	$s->execute(array(':code'=>$search,':brand'=>$search,':category_1'=>$search,':category_2'=>$search,':title'=>$search,':keywords'=>$search,':tags'=>$search,':caption'=>$search,':notes'=>$search));
}elseif($view=='bookings'){
	if(isset($args[0]))$id=(int)$args[0];else $id=0;
}elseif(isset($args[1])){
	$s=$db->prepare("SELECT * FROM content WHERE contentType LIKE :contentType AND LOWER(category_1) LIKE LOWER(:category_1) AND LOWER(category_2) LIKE LOWER(:category_2) AND status LIKE :status ORDER BY ti DESC");
	$s->execute(array(':contentType'=>$view,':category_1'=>str_replace('-',' ',$args[0]),':category_2'=>str_replace('-',' ',$args[1]),':status'=>$status));
}elseif(isset($args[0])){
	$s=$db->prepare("SELECT * FROM content WHERE contentType LIKE :contentType AND LOWER(category_1) LIKE LOWER(:category_1) AND status LIKE :status ORDER BY ti DESC");
	$s->execute(array(':contentType'=>$view,':category_1'=>str_replace('-',' ',$args[0]),':status'=>$status));
	if($s->rowCount()<1){
		if($user['rank']>699){
			$s=$db->prepare("SELECT * FROM content WHERE contentType LIKE :contentType AND LOWER(title) LIKE LOWER(:title) ORDER BY ti DESC");
			$s->execute(array(':contentType'=>$view,':title'=>str_replace('-',' ',$args[0])));
		}else{
			$s=$db->prepare("SELECT * FROM content WHERE contentType LIKE :contentType AND LOWER(title) LIKE LOWER(:title) AND status LIKE :status ORDER BY ti DESC");
			$s->execute(array(':contentType'=>$view,':title'=>str_replace('-',' ',$args[0]),':status'=>$status));
		}
		$show='item';
	}
}else{
	if($user['rank']>699){
		$s=$db->prepare("SELECT * FROM content WHERE contentType LIKE :contentType ORDER BY ti DESC");
		$s->execute(array(':contentType'=>$view));
	}else{
		if($view=='proofs'){
			$s=$db->prepare("SELECT * FROM content WHERE contentType LIKE :contentType AND cid=:cid ORDER BY ti DESC");
			$s->execute(array(':contentType'=>$view,':cid'=>$user['id']));
		}else{
			$s=$db->prepare("SELECT * FROM content WHERE contentType LIKE :contentType AND status LIKE :status AND internal!='1' ORDER BY ti DESC");
			$s->execute(array(':contentType'=>$view,':status'=>$status));
		}
	}
}
if($view=='testimonials'){
	$s=$db->query("SELECT * FROM content WHERE contentType='testimonials'");
}
/* Display Content Selector for Bookings*/
if($view=='bookings'&&$user['rank']<700){
	if(stristr($html,'<print bookable>')){
		$bookable='';
		$sql=$db->query("SELECT id,contentType,code,title FROM content WHERE bookable='1' AND title!='' AND status='published' AND internal!='1' ORDER BY code ASC, title ASC");
		if($sql->rowCount()>0){
			$bookable.='<div class="form-group">';
				$bookable.='<label for="rid" class="control-label col-md-2 col-xs-4">Event/Service</label>';
				$bookable.='<div class="input-group col-md-10 col-xs-8">';
					$bookable.='<select id="rid" class="form-control" name="rid">';
						$bookable.='<option value="0">Select an Item...</option>';
			while($row=$sql->fetch(PDO::FETCH_ASSOC)){
						$bookable.='<option value="'.$row['id'].'"';
						if($id==$row['id']){
							$bookable.=' selected';
						}
						$bookable.='>'.ucfirst($row['contentType']);
						if($row['code']!=''){$bookable.=':'.$row['code'];}
						$bookable.=':'.$row['title'].'</option>';
			}
					$bookable.='</select>';
				$bookable.='</div>';
			$bookable.='</div>';
		}else{
			$bookable='<input type="hidden" name="service" value="0">';
		}
		$html=str_replace('<print bookable>',$bookable,$html);
	}
}
if($view=='bookings'&&$user['rank']>699){
	$html='<main id="content" class="col-md-12"><div class="content"><div id="calendar"></div></div></main>';
}
if($show=='categories'){
	if(stristr($html,'<settings')){
		$matches=preg_match_all('/<settings items=(.*?) contentType=(.*?)>/',$html,$matches);
		$count=$matches[1];
		$html=preg_replace('~<settings.*?>~is','',$html,1);
	}else $count=1;
	if(stristr($html,'<print page=notes>')){
		$html=str_replace('<print page=notes>',$page['notes'],$html);
	}
	if($config['business']){
		$html=str_replace('<print content=seoTitle>',$config['business'],$html);
	}else{
		$html=str_replace('<print content=seoTitle>',$config['seoTitle'],$html);
	}
	$html=str_replace('<print config=address>',$config['address'],$html);
	$html=str_replace('<print config=suburb>',$config['suburb'],$html);
	$html=str_replace('<print config=state>',$config['state'],$html);
	$html=str_replace('<print config=country>',$config['country'],$html);
	if($config['postcode']!=0){
		$html=str_replace('<print config=postcode>',$config['postcode'],$html);
	}
	$html=str_replace('<print config=phone>',$config['phone'],$html);
	$html=str_replace('<print config=mobile>',$config['mobile'],$html);
	if(stristr($html,'<loop>')){
		preg_match('/<loop>([\w\W]*?)<\/loop>/',$html,$matches);
		$item=$matches[1];
		$output='';
		while($r=$s->fetch(PDO::FETCH_ASSOC)){
			$items=$item;
			$items=str_replace('<print content=id>',$r['id'],$items);
			if($view!=$r['contentType']){
				$items=str_replace('<print content=contentType>','<div>'.$r['contentType'].'</div>',$items);
			}else $items=str_replace('<print content=contentType>','',$items);
			if($user['rank']>699){
				$items=str_replace('<print indicator>','<div class="indicator '.$r['status'].'">'.ucfirst($r['status']).'</div>',$items);
			}else{
				$items=str_replace('<print indicator>','',$items);
			}
			$items=str_replace('<print content=schemaType>',$r['schemaType'],$items);
			if(stristr($items,'<print content=dateCreated>')){
				$items=str_replace('<print content=dateCreated>','Created: '.date($config['dateFormat'],$r['ti']),$items);
			}
			if(stristr($items,'<print content=datePublished>')){
				if($r['tis']!=0){
					$items=str_replace('<print content=datePublished>','Published: '.date($config['dateFormat'],$r['tis']),$items);
				}else{
					$items=str_replace('<print content=datePublished>','Published: '.date($config['dateFormat'],$r['ti']),$items);
				}
			}
			if(stristr($items,'<print content=dateEvent>')){
				$dateEvent='';
				if($r['tis']!=0){
					$dateEvent.='Event Date: '.date($config['dateFormat'],$r['tis']);
					if($r['tie']!=0){
						$dateEvent.=' to '.date($config['dateFormat'],$r['tie']);
					}
				}
				$items=str_replace('<print content=dateEvent>',$dateEvent,$items);
			}
			if(stristr($items,'<print content=categories>')){
				if($r['category_1']!=''){
					$categories='Category: <a href="'.$r['contentType'].'/'.str_replace(' ','-',$r['category_1']).'">'.$r['category_1'].'</a>';
					if($r['category_2']!=''){
						$categories.=' / <a href="'.$r['contentType'].'/'.str_replace(' ','-',$r['category_1'].'/'.$r['category_2']).'">'.$r['category_2'].'</a>';
					}
				}else{
					$categories='';
				}
				$items=str_replace('<print content=categories>',$categories,$items);
			}
			if(stristr($items,'<print content=brand>')){
				if($r['brand']!=''){
					$items=str_replace('<print content=brand>','Brand: '.$r['brand'],$items);
				}else{
					$items=str_replace('<print content=brand>','',$items);
				}
			}
			if(stristr($items,'<print content=tags>')){
				if($r['tags']!=''){
					$tags=explode(',',$r['tags']);
					$tagged='Tags: ';
					foreach($tags as $tag){
						$tagged.='<a href="search/'.str_replace(' ','-',$tag).'">#'.$tag.'</a> ';
					}
					$items=str_replace('<print content=tags>',$tagged,$items);
				}else{
					$items=str_replace('<print content=tags>','',$items);
				}
			}
			$items=str_replace('<print content=name>',$r['name'],$items);
			if($r['contentType']=='testimonials'){
				if($r['email']){
					$items=str_replace('<print content=avatar>','http://gravatar.com/avatar/'.md5($r['email']).'?s=100&amp;d=mm',$items);
				}else $items=str_replace('<print content=avatar>',$noavatar,$items);
			}
			if($r['file']&&file_exists('media/'.$r['file'])){
				$items=str_replace('<print content=image>','<div class="cover"><img src="media/'.$r['file'].'" alt="'.$r['title'].'"></div>',$items);
			}else $items=str_replace('<print content=image>','',$items);
			$items=str_replace('<print content=title>',$r['title'],$items);
			if($r['options']{0}==1){// Show Cost
				$cost='<aside class="price text-right" itemprop="offerDetails" itemscope itemtype="http://schema.org/Offer">';
					$cost.='<meta itemprop="currency" content="AUD">';
					$cost.='<h4 itemprop="price">&#36;'.$r['cost'].'</h4>';
				$cost.='</aside>';
				$items=str_replace('<print content=cost>',$cost,$items);
			}else $items=str_replace('<print content=cost>','',$items);
			if($user['rank']>699&&$r['options']{1}==1){
				$sc=$db->prepare("SELECT COUNT(id) as cnt FROM comments WHERE rid=:id");
				$sc->execute(array(':id'=>$r['id']));
				$rc=$sc->fetch(PDO::FETCH_ASSOC);
				if($rc['cnt']>0){
					$items=str_replace('<print content=notes>',preg_replace('/\s+?(\S+)?$/','','<span class="pull-left"><a href="'.$r['contentType'].'/'.str_replace(' ','-',$r['title']).'#comments"><i class="fa fa-comment fa-red"></i></a></span>&nbsp;&nbsp;'.substr(strip_tags($r['notes']),0,201)),$items);
				}
			}else{
				$items=str_replace('<print content=notes>',preg_replace('/\s+?(\S+)?$/','',substr(strip_tags($r['notes']),0,201)),$items);
			}
			if($r['contentType']=='testimonials'&&$user['rank']<900){
				$controls='';
			}else{
				$controls='<a class="btn btn-info" href="'.$r['contentType'].'/'.strtolower(str_replace(' ','-',$r['title'])).'">View</a>';
			}
			if($user['rank']<699){
				if($r['bookable']==1){
					$controls.=' <a class="btn btn-success" href="bookings/'.$r['id'].'">Book '.ucfirst(rtrim($view,'s')).'</a>';
				}else{
					if($view=='inventory'){
						$controls.=' <button class="btn btn-success" onclick="$(\'#cart\').load(\'includes/add_cart.php?id='.$r['id'].'\');">Add to Cart</button>';
					}
				}
			}
			if($user['rank']>699){
				$controls.=' <button class="btn btn-primary';if($r['status']!='delete'){$controls.=' hidden';}$controls.='" onclick="updateButtons(\''.$r['id'].'\',\'content\',\'status\',\'\')">Restore</button>'; 
				$controls.=' <button class="btn btn-danger';if($r['status']=='delete'){$controls.=' hidden';}$controls.='" onclick="updateButtons(\''.$r['id'].'\',\'content\',\'status\',\'delete\')">Delete</button>';
				$controls.=' <button class="btn btn-warning';if($r['status']!='delete'){$controls.=' hidden';}$controls.='" onclick="purge(\''.$r['id'].'\',\'content\')">Purge</button>';
			}
			$items=str_replace('<CONTROLS>',$controls,$items);
			$output.=$items;
		}
		$html=preg_replace('~<loop>.*?<\/loop>~is',$output,$html,1);
		$html=preg_replace('~<item>.*?<\/item>~is','',$html,1);
	}else{
		$html=preg_replace('~<loop>.*?<\/loop>~is','',$html,1);
	}
	$html=preg_replace('~<item>.*?<\/item>~is','',$html,1);
	$html=str_replace('<loop>','',$html);
	$html=str_replace('</loop>','',$html);
	if(stristr($html,'<inc file=')){
		$newDom2=new DOMDocument();
		@$newDom2->loadHTML($html);
		$int=$newDom2->getElementsByTagName('inc');
		foreach($int as $int1){
			$inbed2=$int1->getAttribute('file');
			if($inbed2!=''){
				require$inbed2.'.php';
			}
		}
		preg_match_all('/<loop>([\w\W]*?)<\/loop>/',$html,$matches);
		$item=$matches[1];
	}
}
if($show=='item'){
	$r=$s->fetch(PDO::FETCH_ASSOC);
	preg_match('/<item>([\w\W]*?)<\/item>/',$html,$matches);
	$item=$matches[1];
/* Display Address */
	if($config['options']{1}==1){
		$address='<address class="thumbnail item mini-map';
		if($config['options']{7}==1){$address.=' col-md-6';}else{$address.=' col-md-12';}
		$address.='">';
			$address.='<div class="content">';
				$address.='<h4>'.$config['seoTitle'].'</h4>';
				$address.='<span class="adr">';
					$address.='<span class="street-address" itemprop="streetAddress">'.$config['address'].', '.$config['suburb'].', </span><br>';
					$address.='<span class="region" itemprop="addressLocality">'.$config['state'].', </span>';
					$address.='<span class="country-name" itemprop="addressCountry">'.$config['country'].', </span>';
					$address.='<span class="postal-code" itemprop="postalCode">'.$config['postcode'].'</span>';
				$address.='</span><br>';
				$address.='<br>';
				$address.='<span class="tel" itemprop="telephone">Phone<a href="tel:'.$config['phone'].'">'.$config['phone'].'</a></span><br>';
				$address.='<br>';
				$address.='<span class="tel" itemprop="telephone">Mobile<a href="tel:'.$config['mobile'].'">'.$config['mobile'].'</a></span>';
			$address.='</div>';
		$address.='</address>';
		$item=str_replace('<print config=address>',$address,$item);
	}else{
		$item=str_replace('<print config=address>','',$item);
	}
	if($r['file']&&file_exists('media/'.$r['file'])){
		$item=str_replace('<print content=image>','<img class="intense" src="media/'.$r['file'].'" data-image="media/'.$r['file'].'" alt="'.$r['title'].'" data-title="'.$r['title'].'" data-caption="'.$r['caption'].'">',$item);
	}else $item=str_replace('<print content=image>','',$item);
	$edit='';
/* Title */
	if(isset($user['rank'])&&$user['rank']>899){
		$title='<div class="form-group"><label for="title" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Title</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" class="form-control textinput" value="'.$r['title'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="title"><div id="titlesave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		$item=str_replace('<print content=title>',$title,$item);
	}else{
		$item=str_replace('<print content=title>',$r['title'],$item);
	}
	if(isset($user['rank'])&&$user['rank']>899){
/* Published */
		if($view=='article'||$view=='gallery'||$view=='inventory'||$view=='services'||$view=='portfolio'||$view=='events'||$view=='news'||$view=='testimonials'){
			$edit.='<div class="form-group"><label for="published" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Status</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><select id="status" class="form-control" onchange="update(\''.$r['id'].'\',\'content\',\'status\',$(this).val());"><option value="published"';if($r['status']=='published'){$edit.=' selected';}$edit.='>Published</option><option value="unpublished"';if($r['status']=='unpublished'){$edit.=' selected';}$edit.='>Unpublished</option><option value="delete"';if($r['status']=='delete'){$edit.=' selected';}$edit.='>Delete</option></select></div></div>';
		}
/* Created */
		$edit.='<div class="form-group"><label for="ti" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Created</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="ti" class="form-control" value="'.date($config['dateFormat'],$r['ti']).'" readonly></div></div>';
/* contentType */
		$edit.='<div class="form-group"><label for="contentType" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Content Type</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><select id="contentType" class="form-control" onchange="update(\''.$r['id'].'\',\'content\',\'contentType\',$(this).val());"><option value="article"';if($r['contentType']=='article'){$edit.=' selected';}$edit.='>Article</option><option value="portfolio"';if($r['contentType']=='portfolio'){$edit.=' selected';}$edit.='>Portfolio</option><option value="booking"';if($r['contentType']=='booking'){$edit.=' selected';}$edit.='>Booking</option><option value="events"';if($r['contentType']=='events'){$edit.=' selected';}$edit.='>Event</option><option value="news"';if($r['contentType']=='news'){$edit.=' selected';}$edit.='>News</option><option value="testimonials"';if($r['contentType']=='testimonials'){$edit.=' selected';}$edit.='>Testimonial</option><option value="inventory"';if($r['contentType']=='inventory'){$edit.=' selected';}$edit.='>Inventory</option><option value="service"';if($r['contentType']=='service'){$edit.=' selected';}$edit.='>Service</option><option value="gallery"';if($r['contentType']=='gallery'){$edit.=' selected';}$edit.='>Gallery</option><option value="proofs"';if($r['contentType']=='proofs'){$edit.=' selected';}$edit.='>Proofs</option></select></div></div>';
/* schemaType */
		$edit.='<div class="form-group">';
			$edit.='<label for="schemaType" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Schema Type</label>';
			$edit.='<div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7">';
				$edit.='<select id="schemaType" class="form-control" onchange="update(\''.$r['id'].'\',\'content\',\'schemaType\',$(this).val());">';
					$edit.='<option value="blogPost"';if($r['schemaType']=='blogPost'){$edit.=' selected';}$edit.='>blogPost -> for Articles</option>';
					$edit.='<option value="Product"';if($r['schemaType']=='Product'){$edit.=' selected';}$edit.='>Product -> for Inventory</option>';
					$edit.='<option value="Service"';if($r['schemaType']=='Service'){$edit.=' selected';}$edit.='>Service -> for Services</option>';
					$edit.='<option value="ImageGallery"';if($r['schemaType']=='ImageGallery'){$edit.=' selected';}$edit.='>ImageGallery -> for Gallery Images</option>';
					$edit.='<option value="Review"';if($r['schemaType']=='Review'){$edit.=' selected';}$edit.='>Review -> for Testimonials</option>';
					$edit.='<option value="NewsArticle"';if($r['schemaType']=='NewsArticle'){$edit.=' selected';}$edit.='>NewsArticle -> for News</option>';
					$edit.='<option value="Event"';if($r['schemaType']=='Event'){$edit.=' selected';}$edit.='>Event -> for Events</option>';
					$edit.='<option value="CreativeWork"';if($r['schemaType']=='CreativeWork'){$edit.=' selected';}$edit.='>CreativeWork -> for Portfolio/Proofs</option>';
				$edit.='</select>';
				$edit.='<span class="help-block">Libr8 chooses the appropriate SchemaType when adding Content as defined by <a target="_blank" href="http://www.schema.org/">www.schema.org</a></span>';
			$edit.='</div>';
		$edit.='</div>';
/* Client */
		if($view=='proofs'){
			$edit.='<div class="form-group"><label for="cid" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Client</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><select id="cid" class="form-control" onchange="update(\''.$r['id'].'\',\'content\',\'cid\',$(this).val());"><option value="0">Select a Client...</option>';$cs=$db->query("SELECT * FROM login ORDER BY name ASC, username ASC");while($cr=$cs->fetch(PDO::FETCH_ASSOC)){$edit.='<option value="'.$cr['id'].'"';if($r['cid']==$cr['id']){$edit.=' selected';}$edit.='>'.$cr['username'].':'.$cr['name'].'</option>';}$edit.='</select></div></div>';
		}
/* Author */
		if($view=='article'){
			$edit.='<div class="form-group"><label for="author" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Author</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><select id="uid" class="form-control" onchange="update(\''.$r['id'].'\',\'content\',\'uid\',$(this).val());">';$su=$db->query("SELECT id,username,name FROM login WHERE username!='' AND status!='delete' ORDER BY username ASC, name ASC");while($ru=$su->fetch(PDO::FETCH_ASSOC)){$edit.='<option value="'.$ru['id'].'"';if($ru['id']==$r['uid']){$edit.=' selected';}$edit.='>'.$ru['username'].':'.$ru['name'].'</option>';}$edit.='</select></div></div>';
		}
/* File */
if($view=='article'||$view=='gallery'||$view=='inventory'||$view=='portfolio'||$view=='proofs'||$view=='services'){
			$edit.='<form method="post" target="sp" enctype="multipart/form-data"action="includes/add_data.php"><input type="hidden" name="id" value="'.$r['id'].'"><input type="hidden" name="act" value="add_image"><input type="hidden" name="t" value="content"><input type="hidden" name="c" value="file"><div class="form-group relative"><label for="file" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Image</label><div class="input-group col-lg-9 col-md-8 col-sm-8 col-xs-6"><input type="file" name="fu" class="form-control filestyle" data-icon="false"><div class="input-group-btn"><button class="btn btn-default" onclick="$(\'#block\').css({\'display\':\'block\'});">Upload</button></div></div><div id="file"><img src="';if($r['file']!=''&&file_exists('media/'.$r['file'])){$edit.='media/'.$r['file'];}else{$edit.='images/noimage.jpg';}$edit.='"></div></div></form>';
        }
/* thumb */
if($view=='article'||$view=='gallery'||$view=='inventory'||$view=='portfolio'||$view=='proofs'||$view=='services'){
			$edit.='<form method="post" target="sp" enctype="multipart/form-data" action="includes/add_data.php"><input type="hidden" name="id" value="'.$r['id'].'"><input type="hidden" name="act" value="add_image"><input type="hidden" name="t" value="content"><input type="hidden" name="c" value="thumb"><div class="form-group relative"><label for="thumb" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Thumb</label><div class="input-group col-lg-9 col-md-8 col-sm-8 col-xs-6"><input type="file" name="fu" class="form-control filestyle" data-icon="false"><div class="input-group-btn"><button class="btn btn-default" onclick="$(\'#block\').css({\'display\':\'block\'});">Upload</button></div></div><div id="thumb"><img src="';if($r['thumb']!=''&&file_exists('media/'.$r['thumb'])){$edit.='media/'.$r['thumb'];}else{$edit.='images/noimage.jpg';}$edit.='"></div></div></form>';
		}
/* code */
		if($view=='inventory'||$view=='services'||$view=='events'||$view=='news'){
			$edit.='<div class="form-group"><label for="code" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Code</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="code" class="form-control textinput" value="'.$r['code'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="code" placeholder="Enter a Code..."><div id="codesave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* brand */
		if($view=='inventory'){
			$edit.='<div class="form-group"><label for="brand" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Brand</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="brand" class="form-control textinput" value="'.$r['brand'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="brand" placeholder="Enter a Brand..."><div id="brandsave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* subject */
		if($view=='messages'){
			$edit.='<div class="form-group"><label for="subject" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Subject</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="subject" class="form-control" value="'.$r['subject'].'" readonly></div></div>';
		}
/* from */
		if($view=='messages'){
			$edit.='<div class="form-group"><label for="email" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">From</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="email" class="form-control" value="'.$r['name'].' <'.$r['email'].'>" readonly></div></div>';
		}
/* tis and tie */
		if($view=='bookings'||$view=='events'){
			$edit.='<div class="form-group"><label for="tis" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">';if($view=='events'){$edit.='Event Start';}else{$edit.='Booked For';}$edit.='</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="tis" class="form-control" data-tooltip data-original-title="';if($r['tis']==0){$edit.='Select a Date...';}else{$edit.=date($config['dateFormat'],$r['tis']);}$edit.='" value="';if($r['tis']!=0){$edit.=date('Y-m-d h:m',$r['tis']);}$edit.='" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="tis" placeholder="Select a Date..."><div id="tissave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div><div class="form-group"><label for="tie" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">';if($view=='events'){$edit.='Event End';}else{$edit.='Booking End';}$edit.='</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="tie" class="form-control" data-tooltip data-original-title="';if($r['tie']==''||$r['tie']==0){$edit.='Select a Date...';}else{$edit.=date($config['dateFormat'],$r['tie']);}$edit.='" value="';if($r['tie']!=0){$edit.=date('Y-m-d h:m',$r['tie']);}$edit.='" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="tie" placeholder="Select a Date..."><div id="tiesave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* email */
		if($view=='bookings'||$view=='testimonials'){
			$edit.='<div class="form-group"><label for="email" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Email</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="email" class="form-control textinput" value="'.$r['email'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="email" placeholder="Enter an Email..."><div id="emailsave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* name */
		if($view=='bookings'||$view=='testimonials'||$view=='news'){
			$edit.='<div class="form-group"><label for="name" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Name</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="name" class="form-control textinput" value="'.$r['name'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="name" placeholder="Enter a Name..."><div id="namesave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* url */
		if($view=='testimonials'||$view=='news'){
			$edit.='<div class="form-group"><label for="url" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">URL</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="url" class="form-control textinput" value="'.$r['url'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="url" placeholder="Enter a URL..."><div id="urlsave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* business */
		if($view=='bookings'||$view=='accounts'||$view=='news'||$view=='proofs'){
			$edit.='<div class="form-group"><label for="business" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Business</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="business" class="form-control textinput" value="'.$r['business'].'" data-dbid="'.$r['id'].'" data-dbt="<?php echo$table;?>" data-dbc="business" placeholder="Enter a Business..."><div id="businesssave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* phone */
		if($view=='bookings'||$view=='messages'){
			$edit.='<div class="form-group"><label for="phone" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Phone</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="phone" class="form-control textinput" value="'.$r['phone'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="phone" placeholder="Enter Phone Number..."><div id="phonesave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* ip */
		if($view=='messages'){
			$edit.='<div class="form-group"><label for="ip" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">IP</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="ip" class="form-control" value="'.$r['ip'].'" readonly></div></div>';
		}
/* Article Link */
		if($view=='gallery'||$view=='news'){
			$edit.='<div class="form-group"><label for="assoc" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Link</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><select class="form-control" onchange="update(\''.$r['id'].'\',\'content\',\'assoc\',$(this).val());"><option value="">Select an Article...</option>';$sa=$db->query("SELECT title FROM content WHERE contentType='article'");while($ra=$sa->fetch(PDO::FETCH_ASSOC)){$edit.='<option value="'.strtolower(str_replace(' ','-',$ra['title'])).'"';if($r['assoc']==$ra['title']){$edit.=' selected';}$edit.='>'.$ra['title'].'</option>';}$edit.='</select></div></div>';
		}
/* category_1 */
if($view=='article'||$view=='portfolio'||$view=='gallery'||$view=='inventory'||$view=='services'||$view=='events'||$view=='news'){
			$edit.='<div class="form-group"><label for="category_1" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Category 1</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" class="form-control textinput" id="category_1" value="'.$r['category_1'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="category_1" placeholder="Enter a Category..."><div class="input-group-addon"><i class="fa fa-long-arrow-left"></i></div><select class="form-control" onchange="$(\'#category_1\').val($(this).val());update(\''.$r['id'].'\',\'content\',\'category_1\',$(this).val());"><option value="">Select a Category...</option>';$s=$db->query("SELECT DISTINCT category_1 FROM content WHERE category_1!='' ORDER BY category_1 ASC");while($rs=$s->fetch(PDO::FETCH_ASSOC)){$edit.='<option value="'.$rs['category_1'].'">'.$rs['category_1'].'</option>';}$edit.='</select><div id="category_1save" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* category_2 */
if($view=='article'||$view=='portfolio'||$view=='gallery'||$view=='inventory'||$view=='services'||$view=='events'||$view=='news'){
			$edit.='<div class="form-group"><label for="category_2" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Category 2</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" class="form-control textinput" id="category_2" value="'.$r['category_2'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="category_2" placeholder="Enter a Category..."><div class="input-group-addon"><i class="fa fa-long-arrow-left"></i></div><select class="form-control" onchange="$(\'#category_2\').val($(this).val());update(\''.$r['id'].'\',\'content\',\'category_2\',$(this).val());"><option value="">Select a Category...</option>';$s=$db->query("SELECT DISTINCT category_2 FROM content WHERE category_2!='' ORDER BY category_2 ASC");while($rs=$s->fetch(PDO::FETCH_ASSOC)){$edit.='<option value="'.$rs['category_2'].'">'.$rs['category_2'].'</option>';}$edit.='</select><div id="category_2save" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* cost */
		if($view=='inventory'||$view=='services'){
			$edit.='<div class="form-group"><label for="cost" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Cost</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><div class="input-group-addon">$</div><input type="text" id="cost" class="form-control textinput" value="'.$r['cost'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="cost" placeholder="Enter a Cost..."><div id="costsave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* show cost */
		if($view=='inventory'||$view=='services'){
			$edit.='<div class="form-group"><label for="options0" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Show Cost</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="checkbox" id="options0" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="options" data-dbb="0"';if($r['options']{0}==1){$edit.=' checked';}$edit.='></div></div>';
		}
/* quantity */
		if($view=='inventory'){
			$edit.='<div class="form-group"><label for="quantity" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Quantity</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="quantity" class="form-control textinput" value="'.$r['quantity'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="quantity" placeholder="Enter a Quantity..."><div id="quantitysave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* keywords */
if($view=='article'||$view=='portfolio'||$view=='inventory'||$view=='services'||$view=='gallery'||$view=='events'||$view=='news'){
			$edit.='<div class="form-group"><label for="content_keywords" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Keywords</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="content_keywords" class="form-control textinput" value="'.$r['keywords'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="keywords" placeholder="Enter Keywords.."><div id="content_keywordssave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* tags */
if($view=='article'||$view=='gallery'||$view=='inventory'||$view=='services'||$view=='portfolio'||$view=='events'||$view=='news'||$view=='proofs'){
			$edit.='<div class="form-group"><label for="tags" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Tags</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="tags" class="form-control textinput" value="'.$r['tags'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="tags" placeholder="Enter Tags..."><div id="tagssave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* geoLocation */
		if($view=='article'||$view=='gallery'||$view=='events'){
			$edit.='<div class="well"><h4>Google Maps</h4><div class="form-group"><label for="geoLocation" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">GEO Location</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="geoLocation" class="form-control textinput" value="'.$r['geoLocation'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="geoLocation" placeholder="Enter a Geo Location"><div id="geoLocationsave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
/* geoReference */
			$edit.='<div class="form-group"><label for="geoReference" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">GEO Reference</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="geoReference" class="form-control textinput" value="'.$r['geoReference'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="geoReference" placeholder="Enter a GEO Reference"><div id="geoReferencesave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div></div>';
		}
/* internal */
		$edit.='<div class="form-group"><label for="internal" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Internal</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="checkbox" id="internal0" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="internal" data-dbb="0"';if($r['internal']==1){$edit.=' checked';}$edit.='></div></div>';
/* bookable */
		if($view=='events'||$view=='services'){
			$edit.='<div class="form-group"><label for="bookable" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Bookable</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="checkbox" id="bookable0" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="bookable" data-dbb="0"';if($r['bookable']==1){$edit.=' checked';}$edit.='></div></div>';
		}
/* featured */
if($view=='article'||$view=='gallery'||$view=='services'||$view=='inventory'||$view=='service'||$view=='portfolio'||$view=='events'||$view=='news'){
			$edit.='<div class="form-group"><label for="featured" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Featured</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><div class="input-group-addon"><input type="checkbox" id="featured0" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="featured" data-dbb="0"';if($r['featured']==1){$edit.=' checked';}$edit.='></div><div class="input-group-addon">for</div><select id="fti" class="form-control" onchange="update(\''.$r['id'].'\',\'content\',\'fti\',$(this).val());"><option value="0">Until Disabled</option><option value="86400"';if($r['fti']==86400){$edit.=' selected';}$edit.='>1 Day</option><option value="604800"';if($r['fti']==604800){$edit.=' selected';}$edit.='>7 Days</option><option value="1209600"';if($r['fti']==1209600){$edit.=' selected';}$edit.='>14 Days</option><option value="2592000"';if($r['fti']==2592000){$edit.=' selected';}$edit.='>1 Month</option><option value="15552000"';if($r['fti']==15552000){$edit.=' selected';}$edit.='>6 Months</option><option value="31104000"';if($r['fti']==31101000){$edit.=' selected';}$edit.='>1 Year</option></select></div></div>';
		}
/* caption */
if($view=='article'||$view=='gallery'||$view=='inventory'||$view=='services'||$view=='portfolio'||$view=='events'||$view=='news'){
			$edit.='<div class="form-group"><label for="caption" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Caption</label><div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7"><input type="text" id="caption" class="form-control textinput" value="'.$r['caption'].'" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="caption" placeholder="Enter a Caption..."><div id="captionsave" class="input-group-btn hidden"><button class="btn btn-danger"><i class="fa fa-save"></i></button></div></div></div>';
		}
/* notes */
		$edit.='<form method="post" target="sp" action="includes/update.php"><input type="hidden" name="id" value="'.$r['id'].'"><input type="hidden" name="t" value="content"><input type="hidden" name="c" value="notes"><textarea id="notes" class="form-control summernote" name="da">'.$r['notes'].'</textarea></form>';
/* comments */
		if($view=='article'||$view=='events'||$view=='news'||$view=='proofs'){
			$edit.='<div class="form-group">';
				$edit.='<label for="options1" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Comments</label>';
			if($view!='proofs'){
				$edit.='<div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7">';
					$edit.='<input type="checkbox" id="options1" data-dbid="'.$r['id'].'" data-dbt="content" data-dbc="options" data-dbb="1"';if($r['options']{1}==1){$edit.=' checked';}$edit.='>';
				$edit.='</div>';
			}
			$edit.='</div>';
			$edit.='<div class="clearfix"></div>';
		}
		$item=str_replace('<print content=notes>',$edit,$item);
		$item=preg_replace('~<author>.*?<\/author>~is','',$item,1);
	}else{
		$item=str_replace('<print content=contentType>',$r['contentType'],$item);
		$item=str_replace('<print content=schemaType>',$r['schemaType'],$item);
		if(stristr($item,'<print content=categories>')){
			if($r['category_1']!=''){
				$categories='Category: <a href="'.$view.'/'.str_replace(' ','-',$r['category_1']).'">'.$r['category_1'].'</a>';
				if($r['category_2']!=''){
					$categories.=' / <a href="'.$view.'/'.str_replace(' ','-',$r['category_1'].'/'.$r['category_2']).'">'.$r['category_2'].'</a>';
				}
			}else{
				$categories='';
			}
			$item=str_replace('<print content=categories>',$categories,$item);
		}
		if(stristr($item,'<print content=brand>')){
			if($r['brand']!=''){
				$item=str_replace('<print content=brand>','Brand: '.$r['brand'],$item);
			}else{
				$item=str_replace('<print content=brand>','',$item);
			}
		}
		if(stristr($item,'<print content=tags>')){
			if($r['tags']!=''){
				$tags=explode(',',$r['tags']);
				$tagged='';
				foreach($tags as $tag){
					$tagged.='<a href="search/'.$tag.'">#'.$tag.'</a> ';
				}
				$item=str_replace('<print content=tags>','Tags: '.$tagged,$item);
			}else{
				$item=str_replace('<print content=brand>','',$item);	
			}
		}
		if(stristr($item,'<print content=dateCreated>')){
			$item=str_replace('<print content=dateCreated>','Created: '.date($config['dateFormat'],$r['ti']),$item);
		}
		if(stristr($item,'<print content=datePublished>')){
			if($r['tis']!=0){
				$item=str_replace('<print content=datePublished>','Published: '.date($config['dateFormat'],$r['tis']),$item);
			}else{
				$item=str_replace('<print content=datePublished>','Published: '.date($config['dateFormat'],$r['ti']),$item);
			}
		}
		if(stristr($item,'<print content=dateEvent>')){
			$dateEvent='';
			if($r['tis']!=0){
				$dateEvent.='Event Date: '.date($config['dateFormat'],$r['tis']);
				if($r['tie']!=0){
					$dateEvent.=' to '.date($config['dateFormat'],$r['tie']);
				}
			}
			$item=str_replace('<print content=dateEvent>',$dateEvent,$item);
		}
		if($r['options']{0}==1){// Show Cost
			$cost='<aside class="price text-right" itemprop="offerDetails" itemscope 	itemtype="http://schema.org/Offer"><meta itemprop="currency" content="AUD" /><h4 itemprop="price">&#36;'.$r['cost'].'</h4>';
			if($user['rank']<900){
				if($view=='inventory'){
					$cost.=' <button class="btn btn-success btn-xs" onclick="$(\'#cart\').load(\'includes/add_cart.php?id='.$r['id'].'\');">Add to Cart</button>';
				}
				if($view=='services'){
					$cost.=' <a class="btn btn-success btn-xs" href="bookings/'.$r['id'].'">Book Service</a>';
				}
			}
			$cost.='</aside>';
			$item=str_replace('<print content=cost>',$cost,$item);
		}else $item=str_replace('<print content=cost>','',$item);
		$item=str_replace('<print content=notes>',$r['notes'],$item);
		$authorHTML='';
		if(stristr($item,'<author')&&$view=='article'&&$r['uid']!=0){
			$saD=$db->prepare("SELECT * FROM login WHERE id=:id");
			$saD->execute(array(':id'=>$r['uid']));
			$authorData=$saD->fetch(PDO::FETCH_ASSOC);
			if($saD->rowCount()>0){
				preg_match('/<author>([\w\W]*?)<\/author>/',$item,$matches);
				$authorHTML=$matches[0];
				if(stristr($item,'<print user:avatar>')){
					if($authorData['avatar']&&file_exists('media/'.$authorData['avatar'])){
						$authorHTML=str_replace('<print user:avatar>','media/'.$authorData['avatar'],$authorHTML);
					}elseif($authorData['gravatar']){
						$authorHTML=str_replace('<print user:avatar>','http://www.gravatar.com/avatar/'.md5($authorData['gravatar']).'',$authorHTML);
					}else{
						$authorHTML=str_replace('<print user:avatar>','images/noavatar.jpg',$authorHTML);
					}
				}
				$authorHTML=str_replace('<print user:name>',$authorData['name'],$authorHTML);
				$authorHTML=str_replace('<print user:notes>',$authorData['notes'],$authorHTML);
				if(stristr($item,'<print user:email')) $authorHTML=str_replace('<print user:email>','<a href="mailto:'.$authorData['email'].'"><i class="fa fa-envelope-square fa-2x"></i></a>',$authorHTML);
				if(stristr($item,'<print user:social')){
					$authorSocial='';
					$saS=$db->prepare("SELECT * FROM choices WHERE uid=:uid");
					$saS->execute(array(':uid'=>$r['uid']));
					while($saR=$saS->fetch(PDO::FETCH_ASSOC)){
						$authorSocial.='<a target="_blank" href="'.$saR['url'].'"><i class="fa fa-'.$saR['icon'].' fa-2x"></i></a> ';
					}
					$authorHTML=str_replace('<print user:social>',$authorSocial,$authorHTML);
				}
			}
		}
		$item=preg_replace('~<author>.*?<\/author>~is',$authorHTML,$item,1);
		$seoTitle=$r['title'].' - '.$config['seoTitle'];
		$seoKeywords=$r['keywords'];
		$seoDescription=$r['caption'];
	}
/* Comments */
	if($view=='article'||$view=='events'||$view=='news'||$view=='proofs'){
		$item.='<div id="comments" class="clearfix"><h3>Discussion</h3>';
		if($user['rank']>699){
			$sc=$db->prepare("SELECT * FROM comments WHERE contentType=:contentType AND rid=:rid ORDER BY ti ASC");
		}else{
			$sc=$db->prepare("SELECT * FROM comments WHERE contentType=:contentType AND rid=:rid AND status!='unapproved' ORDER BY ti ASC");
		}
		$sc->execute(array(':contentType'=>$view,':rid'=>$r['id']));
		while($rc=$sc->fetch(PDO::FETCH_ASSOC)){
			$item.='<div id="l_'.$rc['id'].'" class="media';if($rc['status']=='delete'){$item.=' danger';}if($rc['status']=='unapproved'){$item.=' warning';}$item.='">';
				$item.='<div class="media-object pull-left">';
			$su=$db->prepare("SELECT * FROM login WHERE id=:id");
			$su->execute(array(':id'=>$rc['uid']));
			$ru=$su->fetch(PDO::FETCH_ASSOC);
			if($ru['gravatar']!=''){
				$avatar=$ru['gravatar'];
			}elseif($ru['avatar']!=''&&file_exists('files/'.$ru['avatar'])){
				$avatar='files/'.$ru['avatar'];
			}else{
				$avatar='images/noavatar.jpg';
			}
					$item.='<img class="commentavatar img-thumbnail" src="'.$avatar.'">';
				$item.='</div>';
				$item.='<div class="media-body"><div class="well">';
					$item.='<h5 class="media-heading">Name: '.$rc['name'].'</h5>';
					$item.='<time><small class="text-muted">'.date($config['dateFormat'],$rc['ti']).'</small></time>';
					$item.=strip_tags($rc['notes']);
			if($user['rank']>699){
					$item.='<div id="controls-'.$rc['id'].'" class="pull-right">';
						$item.='<button id="approve_'.$rc['id'].'" class="btn btn-success btn-xs';if($rc['status']!='unapproved'){$item.=' hidden';}$item.='" onclick="update(\''.$rc['id'].'\',\'comments\',\'status\',\'\')">Approve</button> '; 
						$item.='<button class="btn btn-danger btn-xs" onclick="purge(\''.$rc['id'].'\',\'comments\')">Delete</button>';
					$item.='</div>';
			}
				$item.='</div></div>';
		}
			$item.='</div>';
		if($r['options']{1}==1||$user['rank']>0){
			$item.='<div class="media">';
				$item.='<div class="media-body col-lg-12 col-md-12 col-sm-12 col-xs-12">';
					$item.='<iframe name="comments" id="comments" class="hidden"></iframe>';
					$item.='<form role="form" target="comments" method="post" action="includes/add_data.php">';
						$item.='<input type="hidden" name="act" value="add_comment">';
						$item.='<input type="hidden" name="rid" value="'.$r['id'].'">';
						$item.='<input type="hidden" name="contentType" value="'.$r['contentType'].'">';
						$item.='<div class="form-group">';
			if($user['rank']>0&&$user['email']!=''){
							$item.='<input type="hidden" name="email" value="'.$user['email'].'">';
			}else{
							$item.='<label for="email" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Email</label>';
							$item.='<div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7">';
								$item.='<input type="text" class="form-control" name="email" value="" placeholder="Enter an Email..." required>';
							$item.='</div>';
			}
			if($user['rank']>0&&$user['name']!=''){
							$item.='<input type="hidden" name="name" value="'.$user['name'].'">';
			}else{
							$item.='<label for="name" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Name</label>';
							$item.='<div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7">';
								$item.='<input type="text" class="form-control" name="name" value="" placeholder="Enter a Name..." required>';
							$item.='</div>';
			}
							$item.='<label for="da" class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">Comment</label>';
							$item.='<div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7">';
								$item.='<textarea id="da" class="form-control" name="da" placeholder="Enter a Comment..." required></textarea>';
							$item.='</div>';
							$item.='<label class="control-label col-lg-2 col-md-3 col-sm-3 col-xs-5">&nbsp;</label>';
							$item.='<div class="input-group col-lg-10 col-md-9 col-sm-9 col-xs-7">';
								$item.='<button class="btn btn-success btn-block">Add Comment</button>';
							$item.='</div>';
						$item.='</div>';
					$item.='</form>';
				$item.='</div>';
			$item.='</div>';
		}
		$item.='</div>';
	}
	$item=str_replace('<CONTROLS>','More',$item);
	$html=preg_replace('~<item>.*?<\/item>~is',$item,$html,1);
	$html=preg_replace('~<settings.*?>~is','',$html,1);
	$html=preg_replace('~<loop>.*?<\/loop>~is','',$html,1);
	$html=str_replace('<print page=notes>','',$html);
	if(stristr($html,'<inc file=')){
		$newDom2=new DOMDocument();
		@$newDom2->loadHTML($html);
		$int=$newDom2->getElementsByTagName('inc');
		foreach($int as $int1){
			$inbed2=$int1->getAttribute('file');
			if($inbed2!=''){
				require$inbed2.'.php';
			}
		}
		preg_match_all('/<loop>([\w\W]*?)<\/loop>/',$html,$matches);
		$item=$matches[1];
	}
}
$content.=$html;
