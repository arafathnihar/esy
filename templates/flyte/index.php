<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.flyte
 *
 * @copyright   Copyright (C) 2013 - 2021 ThemeParrot.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
$app = JFactory::getApplication();
$doc = JFactory::getDocument();
$this->language = $doc->language;
$this->direction = $doc->direction;
$doc->addStyleSheet('templates/'.$this->template.'/css/bootstrap.css');
$doc->addStyleSheet('templates/'.$this->template.'/css/template.css');
$doc->addStyleSheet('templates/'.$this->template.'/css/css3.css');
$doc->addStyleSheet('templates/'.$this->template.'/css/bootstrap-responsive.css');


if($this->countModules('content-right')) {
	$content_span = '9';
} else {
	$content_span = '12';
}

if($this->countModules('top1 and top2 and top3')) {
	$top_span = 4;
}
 elseif( $this->countModules('top1 and top2')
		|| $this->countModules('top1 and top3')
		|| $this->countModules('top2 and top1')
		|| $this->countModules('top2 and  top3')
	    || $this->countModules('top3 and top1')
		|| $this->countModules('top3 and top2')
		){
	$top_span = 6;
}elseif( $this->countModules('top1')
		||$this->countModules('top2')
		||$this->countModules('top3')
		){
	$top_span= 12;
}
	
if($this->countModules('bottom1 and bottom2 and bottom3 and bottom4')) {
    $bottom_span = 3;
}elseif( $this->countModules('bottom1 and bottom2 and bottom3') 
		|| $this->countModules('bottom2 and bottom3 bottom4')
		|| $this->countModules('bottom3 and bottom4 and bottom1')
		) {
	$bottom_span = 4;
}elseif( $this->countModules('bottom1 and bottom2')
		|| $this->countModules('bottom1 and bottom3')
		|| $this->countModules('bottom1 and bottom4')
		|| $this->countModules('bottom2 and bottom1')
		|| $this->countModules('bottom2 and  bottom3')
		|| $this->countModules('bottom2 and bottom4')
		|| $this->countModules('bottom3 and bottom1')
		|| $this->countModules('bottom3 and bottom2')
		|| $this->countModules('bottom3 and  bottom4')
		|| $this->countModules('bottom4 and  bottom1')
		|| $this->countModules('bottom4 and bottom2')
		|| $this->countModules('bottom4 and bottom3')
		){
	$bottom_span = 6;
}elseif( $this->countModules('bottom1')
		||$this->countModules('bottom2')
		||$this->countModules('bottom3')
		||$this->countModules('bottom4')
		){
	$bottom_span= 12;
}

if($this->countModules('content-bottom1 and content-bottom2 and content-bottom3 and content-bottom4')) {
	$content_bottom_span = 3;
}elseif( $this->countModules('content-bottom1 and content-bottom2 and content-bottom3')
		|| $this->countModules('content-bottom2 and content-bottom3 content-bottom4')
		|| $this->countModules('content-bottom3 and content-bottom4 and content-bottom1')
) {
	$content_bottom_span = 4;
}elseif( $this->countModules('content-bottom1 and content-bottom2')
		|| $this->countModules('content-bottom1 and content-bottom3')
		|| $this->countModules('content-bottom1 and content-bottom4')
		|| $this->countModules('content-bottom2 and content-bottom1')
		|| $this->countModules('content-bottom2 and  content-bottom3')
		|| $this->countModules('content-bottom2 and content-bottom4')
		|| $this->countModules('content-bottom3 and content-bottom1')
		|| $this->countModules('content-bottom3 and content-bottom2')
		|| $this->countModules('content-bottom3 and  content-bottom4')
		|| $this->countModules('content-bottom4 and  content-bottom1')
		|| $this->countModules('content-bottom4 and content-bottom2')
		|| $this->countModules('content-bottom4 and content-bottom3')
){
	$content_bottom_span = 6;
}elseif( $this->countModules('content-bottom1')
		||$this->countModules('content-bottom2')
		||$this->countModules('content-bottom3')
		||$this->countModules('content-bottom4')
){
	$content_bottom_span = 12;
}


if($this->countModules('footer1 and footer2 and footer3 and footer4')) {
	$footer_span = 3;
}elseif( $this->countModules('footer1 and footer2 and footer3')
		|| $this->countModules('footer2 and footer3 footer4')
		|| $this->countModules('footer3 and footer4 and footer1')
) {
	$footer_span = 4;
}elseif( $this->countModules('footer1 and footer2')
		|| $this->countModules('footer1 and footer3')
		|| $this->countModules('footer1 and footer4')
		|| $this->countModules('footer2 and footer1')
		|| $this->countModules('footer2 and  footer3')
		|| $this->countModules('footer2 and footer4')
		|| $this->countModules('footer3 and footer1')
		|| $this->countModules('footer3 and footer2')
		|| $this->countModules('footer3 and  footer4')
		|| $this->countModules('footer4 and  footer1')
		|| $this->countModules('footer4 and footer2')
		|| $this->countModules('footer4 and footer3')
){
	$footer_span = 6;
}elseif( $this->countModules('footer1')
		||$this->countModules('footer2')
		||$this->countModules('footer3')
		||$this->countModules('footer4')
){
	$footer_span=12;
}

$siteName = $this->params->get('sitetitle', 'FlyteTees');

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>

	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<jdoc:include type="head" />
	<?php JHtml::_('bootstrap.framework'); ?>
	 <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="#">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="#">
      <link rel="apple-touch-icon-precomposed" sizes="72x72" href="#">
                   <link rel="apple-touch-icon-precomposed" href="#">
                   
     <?php if($this->params->get('googleFont', 1)):?>               
	<link href='http://fonts.googleapis.com/css?family=<?php echo $this->params->get('googleFontName', 'Open+Sans:400,300,600');?>' rel='stylesheet' type='text/css' />
	<?php endif;?>
		<style type="text/css">
		.container
			{
			max-width:960px;
			margin: 0 auto;
			}
		
		body {
			background: <?php echo $this->params->get('templateBackgroundColor', '#FFFFFF'); ?>;
		}
			
		p,h1,h2,h3,h4,h5,h6,.site-title{
			font-family: '<?php echo str_replace('+', ' ', $this->params->get('googleFontName'));?>', sans-serif;
		}
		
			
		a {
			color: <?php echo $this->params->get('linkColor', '#4A4A4A');?>;
		}
		
		a:hover, a:focus, a:active {
			color: <?php echo $this->params->get('linkHoverColor', '#DE6E53');?>
		}
		
		
		.header {
			border-top:8px solid <?php echo $this->params->get('templateColor', '#DE6E53');?>;
			border-bottom:2px solid <?php echo $this->params->get('templateColor', '#DE6E53');?>;
		}
		
		.footer {
			border-top:8px solid <?php echo $this->params->get('templateColor', '#DE6E53');?>;
		}
		
		</style>
     
 
	<!--[if lt IE 9]>
		<script src="<?php echo $this->baseurl ?>/media/jui/js/html5.js"></script>
	<![endif]-->
	
</head>
<body>
    <!-- header section starts -->
	  <section class="header"><!-- header starts here --> 
	    			<!-- start navigation -->		
	        			<div class="navbar navbar-static-top navigation">
								<div class="navbar-inner">
									<div class="container">
										
									  <button class="btn btn-navbar" data-target=".nav-collapse" data-toggle="collapse" type="button">
										 <span class="icon-bar"></span>
										 <span class="icon-bar"></span>
										 <span class="icon-bar"></span>
									  </button> 
									  <a class="brand" href="<?php echo JUri::root(true);?>" >
										<img alt="<?php echo $siteName; ?>" src="<?php echo JURI::root(true); ?>/<?php echo $this->params->get('logoFile'); ?>"/>
									   </a>
									   
									   <div class="nav-collapse collapse">
											<jdoc:include type="modules" name="navigation" style="none" />
										</div>
										
									</div>	
								</div>
							</div>		
		  		<!-- end of navigation -->
	      	  </div>
	      	  
	      	  <!-- cart positioned absolute; -->
	      	  	<?php if($this->countModules('header-right')): ?>
						<aside class="flyte-cart">
							<jdoc:include type="modules" name="header-right" style="none" />												
						</aside>
				<?php endif; ?>
	      	  
	  </section> <!-- header section ends -->
	 
	   <!-- slideshow starts -->
	   <?php if($this->countModules('slideshow')): ?>
	     <section class="slideshow visible-desktop">
	     <div class="container-fluid">
	       <div class="row-fluid">
	         <div class="span12">
	     		<jdoc:include type="modules" name="slideshow" style="xhtml" />
	         </div>
	      </div>
	    </div>
	      </section>
	    <?php endif; ?>
	   <!-- slideshow ends -->
	      
	   
	   <!-- main content starts -->
	<section class="content">
		<div class="container"><!-- Content container starts -->
			<?php if($this->countModules('top1 or top2 or top3')):?>
			<div class="content-top">	
				<div class="row-fluid">		 
				  <!-- top contents starts -->
				<?php if($this->countModules('top1')): ?>
			      	 <div class="span<?php echo $top_span; ?>">
					      <jdoc:include type="modules" name="top1" style="xhtml" />
					 </div>  
					 </div>
				 </div>
				  <?php endif; ?> 
				  <?php if($this->countModules('top2')): ?>
			      	 <div class="span<?php echo $top_span; ?>">
					      <jdoc:include type="modules" name="top2" style="xhtml" />
					 </div>   
				  <?php endif; ?> 
				  <?php if($this->countModules('top3')): ?>
			      	 <div class="span<?php echo $top_span; ?>">
					      <jdoc:include type="modules" name="top3" style="xhtml" />
					 </div> 
				  <?php endif; ?> 
				  <!-- top contents ends -->
			
		<?php endif; ?>		  
				  <!-- content-top section ends -->
			
				  <!-- content-middle starts -->
				  <?php if($this->countModules('content-middle')): ?>
				 	 <div class="content-middle"><!-- content-middle section starts here -->
				 	     <jdoc:include type="modules" name="content-middle" style="xhtml" />
				     </div><!-- content-middle container ends here -->
				    <?php endif; ?>
				    
					<div class="content-main">
						<div class="row-fluid">
							<div class="span<?php echo $content_span; ?>">		    
			   					<jdoc:include type="message" />
								<jdoc:include type="component" />
								
								<?php if($this->countModules('content-bottom1 or content-bottom2 or content-bottom3 or content-bottom4')): ?>	
									  <!-- content-bottom starts -->
									 <div class="content-bottom"> 
									   <div class="row-fluid">
									   <?php if($this->countModules('content-bottom1')): ?>
								      	 <div class="span<?php echo $content_bottom_span; ?>">
										      <jdoc:include type="modules" name="content-bottom1" style="xhtml" />
										 </div>   
									  <?php endif; ?> 
									  <?php if($this->countModules('content-bottom2')): ?>
								      	 <div class="span<?php echo $content_bottom_span; ?>">
										      <jdoc:include type="modules" name="content-bottom2" style="xhtml" />
										 </div>   
									  <?php endif; ?>
									  <?php if($this->countModules('content-bottom3')): ?>
								      	 <div class="span<?php echo $content_bottom_span; ?>">
										      <jdoc:include type="modules" name="content-bottom3" style="xhtml" />
										 </div>   
									  <?php endif; ?>									
									  </div>
									</div>  
									<!-- content-bottom ends -->
							<?php endif; ?>								
							</div>
							<!-- content-right starts -->
						       <?php if($this->countModules('content-right')): ?>
						    	<div class="span3 pull-right">
						    	  <jdoc:include type="modules" name="content-right" style="xhtml" />
						        </div>
						       <?php endif; ?>
						            <!-- content-right ends -->
						</div>
					</div>
					
				
	 	</div> <!-- content container ends -->
	</section> 	<!--  end of content section --> 
	
	            
		<!-- bottom starts -->
		<?php if($this->countModules('bottom1 or bottom2 or bottom3 or bottom4')): ?>	
			<section class="bottom">
			 <div class="container-fluid">
			    <div calss="row-fluid">
				  <?php if($this->countModules('bottom1')): ?>
			      	 <div class="span<?php echo $bottom_span; ?>">
					      <jdoc:include type="modules" name="bottom1" style="xhtml" />
					 </div>   
				  <?php endif; ?> 
				  <?php if($this->countModules('bottom2')): ?>
			      	 <div class="span<?php echo $bottom_span; ?>">
					      <jdoc:include type="modules" name="bottom2" style="xhtml" />
					 </div>   
				  <?php endif; ?>
				  <?php if($this->countModules('bottom3')): ?>
			      	 <div class="span<?php echo $bottom_span; ?>">
					      <jdoc:include type="modules" name="bottom3" style="xhtml" />
					 </div>   
				  <?php endif; ?>
				  <?php if($this->countModules('bottom4')): ?>
			      	 <div class="span<?php echo $bottom_span; ?>">
					      <jdoc:include type="modules" name="bottom4" style="xhtml" />
					 </div>   
				  <?php endif; ?>				  
			    </div>
			</div>
		</section>
		<!-- bottom ends -->
	<?php endif; ?>
		  
	 <!-- footer starts -->
	   <section class="footer">
		  <div class="container"><!-- container starts -->
			    <div calss="row-fluid">
			     <?php if($this->countModules('footer1')): ?>
			      	 <div class="span<?php echo $footer_span; ?>">
					      <jdoc:include type="modules" name="footer1" style="xhtml" />
					 </div>   
				  <?php endif; ?> 
				  <?php if($this->countModules('footer2')): ?>
			      	 <div class="span<?php echo $footer_span; ?>">
					      <jdoc:include type="modules" name="footer2" style="xhtml" />
					 </div>   
				  <?php endif; ?>
				  <?php if($this->countModules('footer3')): ?>
			      	 <div class="span<?php echo $footer_span; ?>">
					      <jdoc:include type="modules" name="footer3" style="xhtml" />
					 </div>   
				  <?php endif; ?>
				  <?php if($this->countModules('footer4')): ?>
			      	 <div class="span<?php echo $footer_span; ?>">
					      <jdoc:include type="modules" name="footer4" style="xhtml" />
					 </div>   
				  <?php endif; ?>	
				  	  <div class="footer-logo">
					   <jdoc:include type="modules" name="footer-logo" style="xhtml" />
					  </div> 
			   </div>
			  
			  </div><!-- container ends -->
			   

			
	  </section>
	 <!-- footer ends -->
	
</body>
</html>
