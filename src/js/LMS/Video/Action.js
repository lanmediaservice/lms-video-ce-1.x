/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Action.js 700 2011-06-10 08:40:53Z macondos $
 */
 
if (!LMS.Video) {
    LMS.Video = {};
}

LMS.Video.Action = {

    getCatalog: function (offset, size, genre, country, order, continuous)
    {
        var self = this;
        this.query({
                'action' : 'Video.getCatalog',
                'offset' : offset,
                'size' : size,
                'genre' : genre? genre : null, 
                'country' : country? country : null,
                'order' : order? order : null
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawCatalog', result.response, continuous);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getBestsellers: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getBestsellers'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawBestsellers', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    search: function (query)
    {
        var self = this;
        this.query({
                'action' : 'Video.search',
                'query' : query
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawSearch', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getBookmarks: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getBookmarks'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawBookmarks', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    deleteBookmark: function (filmId)
    {
        var self = this;
        this.query({
                'action' : 'Video.deleteBookmark',
                'film_id': filmId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('unstarBookmark', filmId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    addBookmark: function (filmId)
    {
        var self = this;
        this.query({
                'action' : 'Video.addBookmark',
                'film_id': filmId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawBookmarks', result.response);
                    self.emit('starBookmark', filmId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    setRating: function (filmId, rating)
    {
        var self = this;
        this.query({
                'action' : 'Video.setRating',
                'film_id': filmId,
                'rating' : rating
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('updateRating', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    getGenres: function (country)
    {
        var self = this;
        this.query({
                'action' : 'Video.getGenres',
                'country' : country
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawGenres', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getCountries: function (genre)
    {
        var self = this;
        this.query({
                'action' : 'Video.getCountries',
                'genre' : genre
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawCountries', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getLastComments: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getLastComments'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawLastComments', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getLastRatings: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getLastRatings'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawLastRatings', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },


    getRandomFilm: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getRandomFilm'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawRandomFilm', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getPopFilms: function ()
    {
        var self = this;
        this.query({
                'action' : 'Video.getPopFilms'
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawPopFilms', result.response);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getFilm: function (filmId, page)
    {
        var self = this;
        this.query({
                'action' : 'Video.getFilm',
                'film_id' : filmId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawFilm', result.response, page);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getMoviePerson: function (personId)
    {
        var self = this;
        this.query({
                'action' : 'Video.getPerson',
                'person_id' : personId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawMoviePerson', result.response, personId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getPerson: function (personId)
    {
        var self = this;
        this.query({
                'action' : 'Video.getPerson',
                'person_id' : personId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawPerson', result.response, personId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getComments: function (filmId)
    {
        var self = this;
        this.query({
                'action' : 'Video.getComments',
                'film_id' : filmId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawComments', result.response, filmId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    getSuggestion: function (text)
    {
        $j.ajax({
            url: "suggestion.php",
            data: ({q : text}),
            cache: true,
            success: function(result) {
                try {
                    var data = result.evalJSON().json.shift();
                    if (data.status=='200') {
                        LMS.Utils.emit('drawSuggestion', data.response);
                    }
                } catch (err){
                    
                }
            }
        });
    },
    
    postComment: function (filmId, text)
    {
        var self = this;
        this.query({
                'action' : 'Video.postComment',
                'film_id' : filmId,
                'text' : text
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('drawComments', result.response, filmId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    deleteComment: function (commentId)
    {
        var self = this;
        this.query({
                'action' : 'Video.deleteComment',
                'comment_id' : commentId
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('postDeleteComment', commentId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    editComment: function (commentId, text)
    {
        var self = this;
        this.query({
                'action' : 'Video.editComment',
                'comment_id' : commentId,
                'text': text
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('postEditComment', commentId);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    changePassword: function (oldPassword, newPassword)
    {
        var self = this;
        this.query({
                'action' : 'Video.changePassword',
                'password_old' : oldPassword,
                'password_new' : newPassword
            },
            function(result) {
                if (200 == result.status) {
                    self.emit('postChangePassword');
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    sendOpinionAndChangeTemplate: function (text, template)
    {
        var self = this;
        this.query({
                'action' : 'Video.sendOpinion',
                'text' : text
            },
            function(result) {
                if (200 == result.status) {
                    ui.setTemplate(template);
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },
    
    setFilmField: function (filmId, field, value)
    {
        var self = this;
        this.query({
                'action' : 'Video.setFilmField',
                'film_id' : filmId,
                'field' : field,
                'value' : value
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    },

    hitFilm: function (filmId)
    {
        var self = this;
        this.query({
                'action' : 'Video.hitFilm',
                'film_id' : filmId
            },
            function(result) {
                if (200 == result.status) {
                } else {
                    self.emit('userError', result.status, result.message);
                }
            }
        );
    }

};