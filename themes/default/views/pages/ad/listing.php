<?php defined('SYSPATH') or die('No direct script access.');?>
     

    <div class="well" id="recomentadion">

        <?if ($category!==NULL):?>
            <?if (file_exists(DOCROOT.'images/categories/'.$category->seoname.'.png')):?>
                <img src="<?=URL::base().'images/categories/'.$category->seoname.'.png'?>" >
            <?endif?>
            <p><?=Text::bb2html($category->description,TRUE)?></p> 
            <i class="glyphicon glyphicon-pencil"></i> 
                <a title="<?=__('New Advertisement')?>" href="<?=Route::url('post_new')?>?category=<?=$category->seoname?>">
                    <?=__('Publish new advertisement')?></a>
        <?else:?>
            <i class="glyphicon glyphicon-pencil"></i> <a title="<?=__('New Advertisement')?>" href="<?=Route::url('post_new')?>"><?=__('Publish new advertisement')?></a>
        <?endif?>


    </div><!--end of recomentadion-->
<div class="btn-group pull-right">
    <button type="button" id="sort" data-sort="<?=core::request('sort')?>" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
        <span class="glyphicon glyphicon-list-alt"></span><?=__('Sort')?> <span class="caret"></span>
    </button>
    <ul class="dropdown-menu" role="menu" id="sort-list">
        <li><a href="?sort=title-asc"><?=__('Name (A-Z)')?></a></li>
        <li><a href="?sort=title-desc"><?=__('Name (Z-A)')?></a></li>
        <?if(core::config('advertisement.price')!=FALSE):?>
        <li><a href="?sort=price-asc"><?=__('Price (Low)')?></a></li>
        <li><a href="?sort=price-desc"><?=__('Price (High)')?></a></li>
        <?endif?>
        <li><a href="?sort=featured"><?=__('Featured')?></a></li>
        <li><a href="?sort=published-desc"><?=__('Newest')?></a></li>
        <li><a href="?sort=published-asc"><?=__('Oldest')?></a></li>
    </ul>
</div>
<div class="clearfix"></div>
    <?if(count($ads)):?>
        <?foreach($ads as $ad ):?>

            <?if($ad->featured >= Date::unix2mysql(time())):?>
                <article class="list well clearfix featured ">
                    <span class="label label-danger pull-right"><?=__('Featured')?></span>
            <?else:?>
                <article class="list well clearfix">
            <?endif?>
                <?if($ad->id_location != 1):?>
                    <a href="<?=Route::url('list',array('location'=>$ad->location->seoname))?>" title="<?=$ad->location->name?>">
                        <span class="label label-default"><?=$ad->location->name?></span>
                    </a>
                <?endif?>
                <h2>
                    <a title="<?= $ad->title;?>" href="<?=Route::url('ad', array('controller'=>'ad','category'=>$ad->category->seoname,'seotitle'=>$ad->seotitle))?>"> <?=$ad->title; ?></a>
                </h2>
                
                
                <div class="picture">
                    <a class="pull-left" title="<?=$ad->title;?>" alt="<?=$ad->title;?>" href="<?=Route::url('ad', array('controller'=>'ad','category'=>$ad->category->seoname,'seotitle'=>$ad->seotitle))?>">
                        <figure>
                            <?if($ad->get_first_image() !== NULL):?>
                                <img src="<?=URL::base()?><?=$ad->get_first_image()?>">
                            <?elseif(file_exists(DOCROOT.'images/categories/'.$ad->category->seoname.'.png')):?>
                                <img src="<?=URL::base().'images/categories/'.$ad->category->seoname.'.png'?>" >
                            <?else:?>
                                <img src="http://www.placehold.it/200x200&text=<?=$ad->category->name?>">
                            <?endif?>
                        </figure>
                    </a>
                </div>
                
                <ul>
                    <?if ($ad->published!=0){?>
                        <li><b><?=__('Publish Date');?>:</b> <?= Date::format($ad->published, core::config('general.date_format'))?></li>
                    <? }?>
                    <?if ($ad->price!=0){?>
                        <li class="price"><?=__('Price');?>: <b><?=i18n::money_format( $ad->price)?></b></li>
                    <?}?>  
                </ul>
             
                <p><?=Text::limit_chars(Text::removebbcode($ad->description), 255, NULL, TRUE);?></p>
                
                <a title="<?= $ad->seotitle;?>" href="<?=Route::url('ad', array('controller'=>'ad','category'=>$ad->category->seoname,'seotitle'=>$ad->seotitle))?>"><i class="glyphicon glyphicon-share"></i><?=__('Read more')?></a>
                <?if ($user !== NULL && $user->id_role == Model_Role::ROLE_ADMIN):?>
                    <br />
                    <div class="toolbar btn btn-primary btn-xs"><i class="glyphicon glyphicon-cog"></i>
                        <div id="user-toolbar-options<?=$ad->id_ad?>" class="hide user-toolbar-options">
                            <a class="btn btn-primary btn-xs" href="<?=Route::url('oc-panel', array('controller'=>'profile','action'=>'update','id'=>$ad->id_ad))?>"><i class="glyphicon glyphicon-edit"></i> <?=__("Edit");?></a> |
                            <a class="btn btn-primary btn-xs" href="<?=Route::url('oc-panel', array('controller'=>'ad','action'=>'deactivate','id'=>$ad->id_ad))?>" 
                                onclick="return confirm('<?=__('Deactivate?')?>');"><i class="glyphicon glyphicon-off"></i><?=__("Deactivate");?>
                            </a> |
                            <a class="btn btn-primary btn-xs" href="<?=Route::url('oc-panel', array('controller'=>'ad','action'=>'spam','id'=>$ad->id_ad))?>" 
                                onclick="return confirm('<?=__('Spam?')?>');"><i class="glyphicon glyphicon-fire"></i><?=__("Spam");?>
                            </a> |
                            <a class="btn btn-primary btn-xs" href="<?=Route::url('oc-panel', array('controller'=>'ad','action'=>'delete','id'=>$ad->id_ad))?>" 
                                onclick="return confirm('<?=__('Delete?')?>');"><i class="glyphicon glyphicon-remove"></i><?=__("Delete");?>
                            </a>

                        </div>
                    </div>
                <?elseif($user !== NULL && $user->id_user == $ad->id_user):?>

                <br/>
                <div class="toolbar btn btn-primary btn-xs"><i class="glyphicon glyphicon-cog"></i>
                    <div id="user-toolbar-options<?=$ad->id_ad?>" class="hide user-toolbar-options">
                        <a class="btn btn-primary btn-xs" href="<?=Route::url('oc-panel', array('controller'=>'profile','action'=>'update','id'=>$ad->id_ad))?>"><i class="glyphicon glyphicon-edit"></i><?=__("Edit");?></a> |
                        <a class="btn btn-primary btn-xs" href="<?=Route::url('oc-panel', array('controller'=>'profile','action'=>'deactivate','id'=>$ad->id_ad))?>" 
                            onclick="return confirm('<?=__('Deactivate?')?>');"><i class="glyphicon glyphicon-off"></i><?=__("Deactivate");?>
                        </a>
                    </div>
                </div>
                <?endif?>
            </article>
        
        <?endforeach?>

        <?=$pagination?>
       <?elseif (count($ads) == 0):?>
       <!-- Case when we dont have ads for specific category / location -->
        <div class="page-header">
            <h3><?=__('We do not have any advertisements in this category')?></h3>
        </div>
      <?endif?>