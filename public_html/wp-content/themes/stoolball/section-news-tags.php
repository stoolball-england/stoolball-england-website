<?php 
    $tags = get_the_tags();
    if ($tags)
    {
        echo '<p class="tags large">Tags: ';
        $i = 0;
         foreach ($tags as $tag) 
         {
             if ($i > 0) echo ", ";
             $i++;
             
             echo '<a href="http://' . $this->GetSettings()->GetDomain() . '/tag/' . $tag->slug . '/" rel="tag sioc:topic" typeof="sioctypes:Tag"><span property="rdfs:label">' . $tag->name . '</span></a>';
         }
        echo '</p>';
    } 
?>