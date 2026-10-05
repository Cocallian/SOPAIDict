from text_to_vector import TextToVector as ttv
import pathlib as path



t2v = ttv(model_name = "BAAI/bge-base-en-v1.5")


InPath = path.Path("C:\\Users\\tygra\\Documents\\Code\\Pythom\\LLM\\Chapter_Exc\\the_verdict.txt")
text = open(InPath, "r", encoding="utf-8").read()
line = text.splitlines()

for line in text.splitlines():
    print("Line:", line)
    print("\n")
    vc = t2v.text_to_embedding(line)
    print("Embedding:", vc)
    print("\n")
    

#vector = t2v.text_to_embedding(text)
#print("Embedding:", vector) 
