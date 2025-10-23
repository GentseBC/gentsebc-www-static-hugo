# Gentse BC website

This repo contains
* content & templates to build the static [website of the Gentse BC](https://www.gentsebc.be)

## Edit process
### For bigger changes requiring local review
Required tools:
* [git](https://git-scm.com/downloads): version controlling system
* [hugo](https://gohugo.io/getting-started/installing/): static site generator

![alt text](documentation/edit_process.png)

### For smaller changes requiring no local review
Edit and commit directly on Github. A rebuild & push to one.com will be triggered automatically.
Review if the build is not failing in [Github Actions](https://github.com/GentseBC/gentsebc-www-static-hugo/actions/workflows/build-and-deploy.yml)


### Image optimization
Fe. for use in nano-albums  
Goal:
- keep git repo size small
- avoid processing time when creating thumbnails
- keep image well orientated as this is not done automatically by hugo

This can be done using `imagemagick` toolsuite
```
brew install imagemagick
```

and then using the mogrify commandline tool

```
# Auto orientation
find . -type f -name "*.jpg" -exec mogrify -auto-orient {} \;

# Resize to 1200 on web quality to lower filesize
find . -type f -name "*.jpg" -exec mogrify -resize 1200 -strip -quality 85 {} \;
```