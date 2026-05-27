<?php
class NewsArticleTag extends Model
{
    protected static string $table = 'news_article_tags';
    // Pivot: id, article_id, tag_id
}
