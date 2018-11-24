# frame-by-frame-optimization

ffmpeg -r 16 -pattern_type glob -i "/Users/jlind/Dev*/RED*/output-test-tx2/*/c*.jpeg" -filter:v "crop=w=1465:h=800:x=30:y=10" -c:v libx264 -preset ultrafast -y ~/Development/RED-SANDSK/ultrafast.mp4

ffmpeg -r 16 -pattern_type glob -i "/Users/jlind/Dev*/RED*/output-test-tx2/*/c*.jpeg" -filter:v "crop=w=1380:h=1010:x=20:y=0" -c:v libx264 -preset veryslow -y ~/Development/RED-SANDSK/veryslow.1380.mp4